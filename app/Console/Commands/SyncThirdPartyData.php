<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\FootfallDailySummary;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TransactionSummary;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncThirdPartyData extends Command
{
    protected $signature = 'sync:thirdparty
        {--days= : Sync last N days (use for backfill / recovery)}
        {--from= : Start date Y-m-d}
        {--to= : End date Y-m-d}';

    protected $description = 'Sync Sales, Footfall and Transaction data';

    private const SALES_API_URL = 'http://202.141.241.251:96/api/Sales/GetSalesByDateV2';
    private const SALES_APP_ID = 10;
    private const SALES_APP_KEY = 'jgiDwu3HwlKgbS9qorWmsVzhJ4oP0s5j';

    /** Connect timeout: fail fast if host unreachable */
    private const SALES_CONNECT_TIMEOUT = 20;

    /** Response timeout: large payloads can be slow */
    private const SALES_TIMEOUT = 300;

    /** Retry attempts for transient failures (timeouts / 5xx) */
    private const SALES_MAX_ATTEMPTS = 3;

    /** Base delay (ms) between retries; doubles each attempt */
    private const SALES_RETRY_BASE_MS = 2000;

    private $yofiToken = 'cf8bc76e6373efe9027e1ee50ddb483fa46458c7';

    public function handle()
    {
        $startedAt = microtime(true);
        $hadSalesFailure = false;

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

        // Day-wise sales fetch: smaller payloads, partial success if one day fails
        $this->info('Fetching sales (day-wise with retry)...');
        $salesResult = $this->fetchSalesGroupedByShop($start, $end);
        $salesByShop = $salesResult['grouped'];
        $hadSalesFailure = $salesResult['had_failure'];

        if ($hadSalesFailure && empty($salesByShop)) {
            $this->error('Sales sync failed for all requested days.');
        } elseif ($hadSalesFailure) {
            $this->warn('Sales sync partially failed — some days were skipped.');
        }

        $this->info('Syncing transaction + footfall...');
        $this->syncTransactionAndFootfall($branches, $start, $end);

        $this->info('Saving sales...');
        $savedInvoices = $this->persistSales($branches, $salesByShop);
        $this->info("Sales invoices saved: {$savedInvoices}");

        $seconds = round(microtime(true) - $startedAt, 2);
        $this->info("Completed in {$seconds}s");

        // Non-zero exit when sales completely failed (cron monitors can alert)
        if ($hadSalesFailure && $savedInvoices === 0) {
            return 1;
        }

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

        // Daily cron default: today only (use --days=3 for backfill)
        return [
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
        ];
    }

    /**
     * Fetch sales day-by-day, merge by shop name.
     *
     * @return array{grouped: array<string, array>, had_failure: bool}
     */
    private function fetchSalesGroupedByShop(Carbon $start, Carbon $end): array
    {
        $grouped = [];
        $hadFailure = false;
        $totalInvoices = 0;

        $period = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());

        foreach ($period as $day) {
            /** @var Carbon $day */
            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();
            $label = $dayStart->toDateString();

            $this->line("  → Sales for {$label}");

            $salesList = $this->fetchSalesForDateRange($dayStart, $dayEnd);

            if ($salesList === null) {
                $hadFailure = true;
                $this->error("  ✗ Failed: {$label}");
                continue;
            }

            if (empty($salesList)) {
                $this->warn("  · No sales for {$label}");
                continue;
            }

            foreach ($salesList as $sale) {
                $shopName = $sale['ShopName'] ?? null;
                if (!$shopName) {
                    continue;
                }
                $grouped[$shopName][] = $sale;
            }

            $count = count($salesList);
            $totalInvoices += $count;
            $this->info("  ✓ {$label}: {$count} invoices");
        }

        $this->info("Sales invoices fetched (total): {$totalInvoices}");

        return [
            'grouped' => $grouped,
            'had_failure' => $hadFailure,
        ];
    }

    /**
     * Call Sales API with connect timeout, response timeout, and exponential backoff retries.
     * Returns SalesList array, empty array when API returns no rows, or null on hard failure.
     */
    private function fetchSalesForDateRange(Carbon $start, Carbon $end): ?array
    {
        $params = [
            'AppId' => self::SALES_APP_ID,
            'AppKey' => self::SALES_APP_KEY,
            'SaleFromDate' => $start->format('Y-m-d H:i:s'),
            'SaleToDate' => $end->format('Y-m-d H:i:s'),
        ];

        $lastError = null;

        for ($attempt = 1; $attempt <= self::SALES_MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::withOptions([
                        'connect_timeout' => self::SALES_CONNECT_TIMEOUT,
                        'timeout' => self::SALES_TIMEOUT,
                    ])
                    ->get(self::SALES_API_URL, $params);

                if ($response->successful()) {
                    $salesList = $response->json('SalesList') ?? [];
                    return is_array($salesList) ? $salesList : [];
                }

                $lastError = 'HTTP ' . $response->status();
                Log::warning('Sales API non-success response', [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'from' => $params['SaleFromDate'],
                    'to' => $params['SaleToDate'],
                    'body' => substr($response->body(), 0, 500),
                ]);

                // Retry only on server errors / rate limits
                if ($response->status() < 500 && $response->status() !== 429) {
                    break;
                }
            } catch (ConnectionException $e) {
                $lastError = $e->getMessage();
                Log::warning('Sales API connection/timeout', [
                    'attempt' => $attempt,
                    'from' => $params['SaleFromDate'],
                    'to' => $params['SaleToDate'],
                    'error' => $lastError,
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::error('Sales API unexpected error', [
                    'attempt' => $attempt,
                    'from' => $params['SaleFromDate'],
                    'to' => $params['SaleToDate'],
                    'error' => $lastError,
                ]);
                break;
            }

            if ($attempt < self::SALES_MAX_ATTEMPTS) {
                $delayMs = self::SALES_RETRY_BASE_MS * (2 ** ($attempt - 1));
                $this->warn("  retry {$attempt}/" . self::SALES_MAX_ATTEMPTS . " in " . ($delayMs / 1000) . 's...');
                usleep($delayMs * 1000);
            }
        }

        Log::error('Sales sync request failed after retries', [
            'from' => $params['SaleFromDate'],
            'to' => $params['SaleToDate'],
            'error' => $lastError,
        ]);
        $this->error('Sales API failed: ' . ($lastError ?? 'unknown error'));

        return null;
    }

    private function persistSales($branches, array $salesByShop): int
    {
        $saved = 0;
        $branchesByName = $branches->keyBy('name');

        foreach ($branchesByName as $shopName => $branch) {
            $shopSales = $salesByShop[$shopName] ?? [];
            if (empty($shopSales)) {
                continue;
            }

            $this->saveBranchSales($shopSales);
            $count = count($shopSales);
            $saved += $count;
            $this->info("Sales saved: {$shopName} ({$count} invoices)");
        }

        return $saved;
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
