<?php

namespace App\Console\Commands;

use App\Models\AreaSaleManager;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\Designation;
use App\Models\FootfallDailySummary;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleStaff;
use App\Models\TransactionSummary;
use App\Services\LineItemSyncService;
use App\Services\TargetAchievementNotifier;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
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

    /** Retry attempts for transient failures (timeouts / 5xx) */
    private const SALES_MAX_ATTEMPTS = 5;

    /** Base delay (ms) between retries; doubles each attempt */
    private const SALES_RETRY_BASE_MS = 3000;

    /** Response timeout: large payloads can be slow */
    private const SALES_TIMEOUT = 480;

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

         $this->info('Syncing branches from GetSalesByDateV2 ShopName...');
        $this->syncBranchesFromSalesApi($salesByShop);

        $this->info('Syncing sales staff from GetSalesByDateV2...');
        try {
            $this->syncSaleStaffFromSalesApi($salesByShop);
        } catch (\Throwable $e) {
            Log::error('Sales staff sync failed (continuing to save sales)', ['error' => $e->getMessage()]);
            $this->error('Sales staff sync failed (continuing to save sales): ' . $e->getMessage());
        }

        $branches = Branch::whereNotNull('branch_id')
            ->where('branch_id', '!=', '')
            ->get();

        if ($hadSalesFailure && empty($salesByShop)) {
            $this->error('Sales sync failed for all requested days.');
        } elseif ($hadSalesFailure) {
            $this->warn('Sales sync partially failed — some days were skipped.');
        }

        // Save sales first so txn/footfall failures never block invoice persistence
        $this->info('Saving sales...');
        $savedInvoices = $this->persistSales($branches, $salesByShop);
        $this->info("Sales invoices saved: {$savedInvoices}");

        try {
            app(TargetAchievementNotifier::class)->notifyCurrentMonthHits();
        } catch (\Throwable $e) {
            Log::error('Target achievement notify failed', ['error' => $e->getMessage()]);
            $this->warn('Target achievement notify failed: ' . $e->getMessage());
        }

        try {
            // V3 is a separate API used only for line_items (not sales)
            $this->info('Syncing line items from GetSalesByDateV3...');
            $this->syncLineItemsFromV3($start, $end);
            
            $this->info('Syncing transactions...');
            $this->syncTransactionSummaries($branches);
        } catch (\Throwable $e) {
            Log::error('Transaction sync failed', ['error' => $e->getMessage()]);
            $this->error('Transaction sync failed (sales already saved): ' . $e->getMessage());
        }

        try {
            $this->info('Syncing footfall (all branches, one request)...');
            $this->syncAllFootfall($branches, $start, $end);
        } catch (\Throwable $e) {
            Log::error('Footfall sync failed', ['error' => $e->getMessage()]);
            $this->error('Footfall sync failed (sales already saved): ' . $e->getMessage());
        }

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

    private function syncBranchesFromSalesApi(array $salesByShop): void
    {
        $created = 0;
        $filled = 0;

        foreach ($salesByShop as $shopName => $shopSales) {
            $shopName = trim((string) $shopName);
            if ($shopName === '') {
                continue;
            }

            $shopId = null;
            foreach ($shopSales as $sale) {
                $id = trim((string) ($sale['ShopId'] ?? ''));
                if ($id !== '') {
                    $shopId = $id;
                    break;
                }
            }

            $branch = Branch::where('name', $shopName)->first();

            if (!$branch) {
                Branch::create([
                    'name' => $shopName,
                    'branch_id' => $shopId,
                    'city' => null,
                    'address' => null,
                    'region_id' => null,
                ]);
                $created++;
                continue;
            }

            if ((string) $branch->branch_id === '' && $shopId !== null) {
                $branch->branch_id = $shopId;
                $branch->save();
                $filled++;
            }
        }

        $this->info("Branches from API: {$created} created, {$filled} Branch ID filled");
    }

    private function persistSales($branches, array $salesByShop): int
    {
        $saved = 0;
        // $lineItemNames = [];
        // Build name lookup for logging / optional branch mapping (NOT used to skip saves)
        $branchNames = $branches->pluck('name')->filter()->map(function ($n) {
            return strtolower(trim($n));
        })->flip();

        // Save EVERY shop returned by the Sales API — do not drop unknown shops
        foreach ($salesByShop as $shopName => $shopSales) {
            if (empty($shopSales) || trim((string) $shopName) === '') {
                continue;
            }

            $this->saveBranchSales($shopSales);
            // $lineItemNames = array_merge($lineItemNames, $this->saveBranchSales($shopSales));
            $count = count($shopSales);
            $saved += $count;

            $known = $branchNames->has(strtolower(trim((string) $shopName)));
            $tag = $known ? '' : ' [shop not in branches table — still saved]';
            $this->info("Sales saved: {$shopName} ({$count} invoices){$tag}");
        }

        // Warn about API shops missing from branches (footfall/targets still need branch rows)
        $apiShops = collect(array_keys($salesByShop))->map(fn ($n) => strtolower(trim((string) $n)));
        $missingBranches = $apiShops->filter(fn ($n) => !$branchNames->has($n))->values();
        if ($missingBranches->isNotEmpty()) {
            $this->warn('API shops not found in branches table (sales still saved): ' . $missingBranches->take(20)->implode(', '));
        }
        // if (!empty($lineItemNames)) {
        //     app(LineItemSyncService::class)->syncFromNames($lineItemNames);
        // }
        return $saved;
    }

    private function syncSaleStaffFromSalesApi(array $salesByShop): void
    {
        $people = [];

        foreach ($salesByShop as $shopName => $shopSales) {
            $shopName = trim((string) $shopName);
            foreach ($shopSales as $sale) {
                foreach (($sale['data'] ?? []) as $item) {
                    $code = trim((string) ($item['SalesPersonCode'] ?? ''));
                    if ($code === '') {
                        continue;
                    }

                    $name = trim((string) ($item['SalesPersonName'] ?? ''));
                    $people[$code] = [
                        'code' => $code,
                        'name' => $name !== '' ? $name : $code,
                        'shop_name' => $shopName,
                    ];
                }
            }
        }

        if (empty($people)) {
            $this->info('Sales staff from API: none found');
            return;
        }

        $defaultDesignation = Designation::query()
            ->whereRaw('LOWER(name) LIKE ?', ['%staff%'])
            ->orderBy('id')
            ->first()
            ?? Designation::query()->orderBy('id')->first();

        if (!$defaultDesignation) {
            $this->warn('No designations found — skipping sales staff sync.');
            Log::warning('Sales staff sync skipped: no designations');
            return;
        }

        $branchesByName = Branch::query()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->get()
            ->keyBy('name');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($people as $person) {
            $code = $person['code'];

            if (
                AreaSaleManager::where('employee_id', $code)->exists()
                || BranchManager::where('employee_id', $code)->exists()
            ) {
                $skipped++;
                continue;
            }

            $branch = $branchesByName->get($person['shop_name']);
            $branchId = $branch ? $branch->id : null;
            $staff = SaleStaff::where('employee_id', $code)->first();

            if (!$staff) {
                SaleStaff::create([
                    'employee_id' => $code,
                    'name' => $person['name'],
                    'email' => $this->uniqueSyncEmail($code),
                    'password' => Hash::make('12345678'),
                    'designation_id' => $defaultDesignation->id,
                    'branch_id' => $branchId,
                    'image' => 'public/admin/assets/images/avator.png',
                ]);
                $created++;
                continue;
            }

            $changed = false;

            if ($person['name'] !== '' && $staff->name !== $person['name']) {
                $staff->name = $person['name'];
                $changed = true;
            }

            if ($branchId && (int) $staff->branch_id !== (int) $branchId) {
                $staff->branch_id = $branchId;
                $changed = true;
            }

            if (empty($staff->designation_id)) {
                $staff->designation_id = $defaultDesignation->id;
                $changed = true;
            }

            if ($changed) {
                $staff->save();
                $updated++;
            }
        }

        $this->info("Sales staff from API: {$created} created, {$updated} updated, {$skipped} skipped (ASM/BM)");
    }

    private function uniqueSyncEmail(string $code): string
    {
        $local = strtolower(preg_replace('/[^a-zA-Z0-9._-]+/', '', $code));
        if ($local === '') {
            $local = 'staff' . substr(md5($code), 0, 8);
        }

        $email = $local . '@limelight.sync';
        $i = 1;
        while (SaleStaff::where('email', $email)->exists()) {
            $email = $local . $i . '@limelight.sync';
            $i++;
        }

        return $email;
    }

    /**
     * Stable unique key: shop identity + invoice number.
     * InvoiceNo alone is reused across shops in the upstream API.
     */
    private function makeSaleKey(?string $shopId, ?string $shopName, $invoiceNo): string
    {
        $shopPart = trim((string) (($shopId !== null && $shopId !== '')
            ? $shopId
            : ($shopName ?: 'unknown')));

        return $shopPart . '|' . trim((string) $invoiceNo);
    }

    private function saveBranchSales(array $sales): void
    
    {
        // $lineItemNames = [];
        foreach ($sales as $sale) {
            $invoiceNo = $sale['InvoiceNo'] ?? null;
            $shopName = $sale['ShopName'] ?? null;
            $shopId = isset($sale['ShopId']) ? (string) $sale['ShopId'] : null;

            if ($invoiceNo === null || $invoiceNo === '') {
                continue;
            }

            $saleKey = $this->makeSaleKey($shopId, $shopName, $invoiceNo);

            $invoice = Sale::updateOrCreate(
                [
                    'sale_key' => $saleKey,
                ],
                [
                    'sale_key' => $saleKey,
                    'invoice_id' => $invoiceNo,
                    'sales_id' => $sale['Id'] ?? $sale['Id'] ?? $sale['Id'] ?? null,
                    'sale_from_date' => $sale['SaleFromDate'] ?? null,
                    'sale_to_date' => $sale['SaleToDate'] ?? null,
                    'date' => Carbon::parse($sale['Date']),
                    'coupon_no' => $sale['CouponNo'] ?? null,
                    'shop_id' => $shopId,
                    'shop_name' => $shopName,
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
                $productCode = $item['ProductCode'] ?? null;
                if ($productCode === null || $productCode === '') {
                    continue;
                }

                SaleItem::updateOrCreate(
                    [
                        'sale_key' => $saleKey,
                        'product_code' => $productCode,
                    ],
                    [
                        'sale_key' => $saleKey,
                        'invoice_id' => $invoice->invoice_id,
                        'shop_name' => $shopName,
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
    /**
     * Sync unique LineItem column values from GetSalesByDateV3 into line_items.
     * Independent of V2 sales sync — failures here do not roll back sales.
     */
    private function syncLineItemsFromV3(Carbon $start, Carbon $end): void
    {
        try {
            $result = app(LineItemSyncService::class)->syncFromV3Api($start, $end);
            $this->info(
                'Line items synced from V3: '
                . $result['unique'] . ' unique, '
                . $result['created'] . ' new'
                . ($result['had_failure'] ? ' (some days failed)' : '')
            );
        } catch (\Throwable $e) {
            Log::error('Line items V3 sync failed', ['error' => $e->getMessage()]);
            $this->warn('Line items sync failed: ' . $e->getMessage());
        }
        
    }

    private function syncTransactionSummaries($branches): void
    {
        foreach ($branches->chunk(10) as $chunk) {
            $responses = Http::pool(function ($pool) use ($chunk) {
                $requests = [];

                foreach ($chunk as $branch) {
                    $branchKey = $branch->id;

                    $requests["txn_{$branchKey}"] = $pool->as("txn_{$branchKey}")
                        ->timeout(60)
                        ->withHeaders([
                            'Authorization' => 'Token ' . $this->yofiToken,
                        ])
                        ->get('https://unov.yofi.link/api/outlet/' . $branch->branch_id . '/');
                }

                return $requests;
            });

            foreach ($chunk as $branch) {
                $this->saveTransactionSummary($branch, $responses["txn_{$branch->id}"] ?? null);
            }
        }
    }

    /**
     * One API hit returns every shop's footfall for the date range.
     */
    private function syncAllFootfall($branches, $start, $end): void
    {
        try {
            $response = Http::timeout(120)
                ->get('https://unov.yofi.link/api/footfall/daily/', [
                    'token' => $this->yofiToken,
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                ]);
        } catch (\Throwable $e) {
            Log::error('Footfall sync request failed', ['error' => $e->getMessage()]);
            $this->error('Footfall sync request failed: ' . $e->getMessage());
            return;
        }

        if (!$response->successful()) {
            Log::warning('Footfall sync failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            $this->warn('Footfall sync failed (HTTP ' . $response->status() . ')');
            return;
        }

        $data = $response->json();
        if (!is_array($data)) {
            $this->warn('Footfall sync returned invalid payload');
            return;
        }

        $branchesByShopId = $branches->keyBy(function ($branch) {
            return (string) $branch->branch_id;
        });

        $savedShops = 0;
        $skippedShops = 0;

        foreach ($data as $shop) {
            if (!is_array($shop)) {
                continue;
            }

            $shopId = isset($shop['shop_id']) ? (string) $shop['shop_id'] : '';
            if ($shopId === '' || !isset($shop['footfall_daily_summary']) || !is_array($shop['footfall_daily_summary'])) {
                $skippedShops++;
                continue;
            }

            $branch = $branchesByShopId->get($shopId);
            if (!$branch) {
                $skippedShops++;
                continue;
            }

            foreach ($shop['footfall_daily_summary'] as $row) {
                if (!is_array($row) || empty($row['date'])) {
                    continue;
                }

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

            $savedShops++;
        }

        $this->info("Footfall saved for {$savedShops} shops" . ($skippedShops ? " (skipped {$skippedShops})" : ''));
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
}
