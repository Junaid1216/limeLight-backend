<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\FootfallDailySummary;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TransactionSummary;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncThirdPartyData extends Command
{
    protected $signature = 'sync:thirdparty
        {--days= : Sync last N days (use for backfill / recovery)}
        {--from= : Start date Y-m-d}
        {--to= : End date Y-m-d}';

    protected $description = 'Sync Sales, Footfall and Transaction data';

    private $yofiToken = 'cf8bc76e6373efe9027e1ee50ddb483fa46458c7';

    public function handle()
    {
        $startedAt = microtime(true);

        $branches = Branch::whereNotNull('branch_id')
            ->where('branch_id', '!=', '')
            ->get();

        if ($branches->isEmpty()) {
            $this->warn('No branches found');
            return 0;
        }

        [$start, $end] = $this->resolveDateRange();

        $this->info('Date range: ' . $start->toDateTimeString() . ' -> ' . $end->toDateTimeString());
        $this->info('Branches: ' . $branches->count());

        // Sales API returns all shops for same date range — fetch once (biggest speed win)
        $this->info('Fetching sales (once for all branches)...');
        $salesByShop = $this->fetchSalesGroupedByShop($start, $end);

        // Parallel transaction + footfall requests
        $this->info('Syncing transaction + footfall...');
        $this->syncTransactionAndFootfall($branches, $start, $end);

        // Save sales per branch from already-fetched data
        $this->info('Saving sales...');
        $branchesByName = $branches->keyBy('name');

        foreach ($branchesByName as $shopName => $branch) {
            $shopSales = $salesByShop[$shopName] ?? [];

            if (empty($shopSales)) {
                continue;
            }

            $this->saveBranchSales($shopSales);
            $this->info("Sales saved: {$shopName} (" . count($shopSales) . ' invoices)');
        }

        $seconds = round(microtime(true) - $startedAt, 2);
        $this->info("Completed in {$seconds}s");

        return 0;
    }

    private function resolveDateRange(): array
    {
        if ($this->option('from')) {
            $start = Carbon::parse($this->option('from'))->startOfDay();
            $end = $this->option('to')
                ? Carbon::parse($this->option('to'))->endOfDay()
                : Carbon::today()->endOfDay();

            return [$start, $end];
        }

        if ($this->option('days')) {
            $days = max(1, (int) $this->option('days'));

            return [
                Carbon::today()->subDays($days - 1)->startOfDay(),
                Carbon::today()->endOfDay(),
            ];
        }

        // Default: 3-day window so a missed/failed cron run still gets backfilled
        return [
            Carbon::today()->subDays(2)->startOfDay(),
            Carbon::today()->endOfDay(),
        ];
    }

    private function fetchSalesGroupedByShop($start, $end): array
    {
        try {
            $response = Http::timeout(120)->get('http://202.141.241.251:96/api/Sales/GetSalesByDateV2', [
                'AppId' => 10,
                'AppKey' => 'jgiDwu3HwlKgbS9qorWmsVzhJ4oP0s5j',
                'SaleFromDate' => $start->format('Y-m-d H:i:s'),
                'SaleToDate' => $end->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Sales sync request failed', ['error' => $e->getMessage()]);
            $this->error('Sales API failed: ' . $e->getMessage());
            return [];
        }

        if (!$response->successful()) {
            Log::error('Sales sync HTTP error', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            $this->error('Sales API HTTP ' . $response->status());
            return [];
        }

        $data = $response->json();
        $salesList = $data['SalesList'] ?? [];

        if (empty($salesList)) {
            $this->warn('No sales returned from API');
            return [];
        }

        $grouped = [];

        foreach ($salesList as $sale) {
            $shopName = $sale['ShopName'] ?? null;

            if (!$shopName) {
                continue;
            }

            $grouped[$shopName][] = $sale;
        }

        $this->info('Sales invoices fetched: ' . count($salesList));

        return $grouped;
    }

    private function saveBranchSales(array $sales): void
    {
        foreach ($sales as $sale) {
            $invoice = Sale::updateOrCreate(
                [
                    'invoice_id' => $sale['InvoiceNo'],
                ],
                [
                    'invoice_id' => $sale['InvoiceNo'],
                    'sale_from_date' => $sale['SaleFromDate'] ?? null,
                    'sale_to_date' => $sale['SaleToDate'] ?? null,
                    'date' => Carbon::parse($sale['Date']),
                    'coupon_no' => $sale['CouponNo'] ?? null,
                    'shop_id' => $sale['ShopId'] ?? null,
                    'shop_name' => $sale['ShopName'] ?? null,
                    'mobile_number' => $sale['MobileNumber'] ?? null,
                    'customer_name' => $sale['CustomerName'] ?? null,
                    'gender' => $sale['Gender'] ?? null,
                    'net_total' => $sale['NetTotal'] ?? 0,
                    'comments' => $sale['Comments'] ?? null,
                    'additional_comments' => $sale['AdditionalComments'] ?? null,
                ]
            );

            $items = $sale['data'] ?? [];

            foreach ($items as $item) {
                SaleItem::updateOrCreate(
                    [
                        'invoice_id' => $invoice->invoice_id,
                        'product_code' => $item['ProductCode'],
                    ],
                    [
                        'product_name' => $item['ProductName'] ?? null,
                        'product_category' => $item['ProductCategory'] ?? null,
                        'product_sub_category' => $item['ProductSubCategory'] ?? null,
                        'size' => $item['Size'] ?? null,
                        'technical_details' => $item['TechnicalDetails'] ?? null,
                        'color' => $item['Color'] ?? null,
                        'quantity' => $item['Quantity'] ?? 0,
                        'price' => $item['Price'] ?? 0,
                        'discount' => $item['Discount'] ?? 0,
                        'tax' => $item['Tax'] ?? 0,
                        'salesperson_name' => $item['SalesPersonName'] ?? null,
                        'salesperson_code' => $item['SalesPersonCode'] ?? null,
                        'category' => $item['Category'] ?? null,
                    ]
                );
            }
        }
    }

    private function syncTransactionAndFootfall($branches, $start, $end): void
    {
        // Process in chunks to avoid too many parallel connections
        foreach ($branches->chunk(10) as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk, $start, $end) {
                $requests = [];

                foreach ($chunk as $branch) {
                    $branchKey = $branch->id;

                    $requests["txn_{$branchKey}"] = $pool->as("txn_{$branchKey}")
                        ->timeout(60)
                        ->withHeaders([
                            'Authorization' => 'Token ' . $this->yofiToken,
                        ])
                        ->get('https://unov.yofi.link/api/outlet/' . $branch->branch_id . '/');

                    $requests["ff_{$branchKey}"] = $pool->as("ff_{$branchKey}")
                        ->timeout(60)
                        ->get('https://unov.yofi.link/api/footfall/daily/', [
                            'outlet' => $branch->branch_id,
                            'token' => $this->yofiToken,
                            'start' => $start->format('Y-m-d'),
                            'end' => $end->format('Y-m-d'),
                        ]);
                }

                return $requests;
            });

            foreach ($chunk as $branch) {
                $this->saveTransactionSummary($branch, $responses["txn_{$branch->id}"] ?? null);
                $this->saveFootfall($branch, $responses["ff_{$branch->id}"] ?? null);
            }
        }
    }

    private function saveTransactionSummary($branch, $response): void
    {
        if (!$response || !$response->successful()) {
            Log::warning('Transaction sync failed', [
                'branch_id' => $branch->id,
                'outlet' => $branch->branch_id,
                'status' => $response ? $response->status() : null,
            ]);
            return;
        }

        $data = $response->json();
        $items = $data['transaction_summary'] ?? [];

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            TransactionSummary::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'day' => $item['day'],
                ],
                [
                    'total_transactions' => $item['total_transactions'] ?? 0,
                    'total_sales' => $item['total_sales'] ?? 0,
                    'total_items' => $item['total_items'] ?? 0,
                ]
            );
        }
    }

    private function saveFootfall($branch, $response): void
    {
        if (!$response || !$response->successful()) {
            Log::warning('Footfall sync failed', [
                'branch_id' => $branch->id,
                'outlet' => $branch->branch_id,
                'status' => $response ? $response->status() : null,
            ]);
            return;
        }

        $data = $response->json();

        if (!is_array($data)) {
            return;
        }

        foreach ($data as $shop) {
            if (!isset($shop['footfall_daily_summary']) || !is_array($shop['footfall_daily_summary'])) {
                continue;
            }

            foreach ($shop['footfall_daily_summary'] as $row) {
                FootfallDailySummary::updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'date' => $row['date'],
                    ],
                    [
                        'footfall' => $row['footfall'] ?? 0,
                        'on_time' => $row['on_time'] ?? null,
                    ]
                );
            }
        }
    }
}
