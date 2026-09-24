<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\FootfallDailySummary;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TransactionSummary;
use App\Services\LineItemSyncService;
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

        // #region agent log
        $__dbg = function (string $hid, string $msg, array $data = []) {
            file_put_contents(base_path('debug-741ebe.log'), json_encode([
                'sessionId' => '741ebe',
                'runId' => 'post-fix',
                'hypothesisId' => $hid,
                'location' => 'SyncThirdPartyData.php:handle',
                'message' => $msg,
                'data' => $data,
                'timestamp' => (int) round(microtime(true) * 1000),
            ]) . "\n", FILE_APPEND);
        };
        // #endregion

        $branches = Branch::whereNotNull('branch_id')
            ->where('branch_id', '!=', '')
            ->get();

        if ($branches->isEmpty()) {
            $this->warn('No branches found');
            // #region agent log
            $__dbg('H5', 'no branches found', ['branch_count' => 0]);
            // #endregion
            return 0;
        }

        [$start, $end] = $this->resolveDateRange();

        $this->info('Date range: ' . $start->toDateTimeString() . ' -> ' . $end->toDateTimeString());
        $this->info('Branches: ' . $branches->count());

        // #region agent log
        $__dbg('H1', 'sync start', [
            'from' => $start->toDateTimeString(),
            'to' => $end->toDateTimeString(),
            'branch_count' => $branches->count(),
            'sales_before' => \App\Models\Sale::count(),
            'sale_items_before' => \App\Models\SaleItem::count(),
        ]);
        // #endregion

        // Day-wise sales fetch: smaller payloads, partial success if one day fails
        $this->info('Fetching sales (day-wise with retry)...');
        $salesResult = $this->fetchSalesGroupedByShop($start, $end);
        $salesByShop = $salesResult['grouped'];
        $hadSalesFailure = $salesResult['had_failure'];

        // #region agent log
        $shopKeys = array_keys($salesByShop);
        $branchNames = $branches->pluck('name')->all();
        $matched = array_values(array_intersect($shopKeys, $branchNames));
        $unmatched = array_values(array_diff($shopKeys, $branchNames));
        $__dbg('H1', 'sales fetch result', [
            'had_failure' => $hadSalesFailure,
            'shops_fetched' => count($shopKeys),
            'invoices_fetched' => array_sum(array_map('count', $salesByShop)),
            'matched_shops' => count($matched),
            'unmatched_shops' => count($unmatched),
            'unmatched_sample' => array_slice($unmatched, 0, 10),
            'matched_sample' => array_slice($matched, 0, 10),
        ]);
        // #endregion

        if ($hadSalesFailure && empty($salesByShop)) {
            $this->error('Sales sync failed for all requested days.');
        } elseif ($hadSalesFailure) {
            $this->warn('Sales sync partially failed — some days were skipped.');
        }

        // Persist sales immediately so txn/footfall failures cannot block sales
        $this->info('Saving sales...');
        // #region agent log
        $__dbg('H3', 'persistSales starting (before transactions)', [
            'shops_to_persist' => count($matched),
            'in_memory_invoices' => array_sum(array_map('count', $salesByShop)),
        ]);
        // #endregion
        try {
            $savedInvoices = $this->persistSales($branches, $salesByShop);
        } catch (\Throwable $e) {
            // #region agent log
            $__dbg('H4', 'persistSales exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            // #endregion
            throw $e;
        }
        $this->info("Sales invoices saved: {$savedInvoices}");

        // #region agent log
        $__dbg('H3', 'persistSales finished', [
            'saved_invoices' => $savedInvoices,
            'sales_after' => \App\Models\Sale::count(),
            'sale_items_after' => \App\Models\SaleItem::count(),
        ]);
        // #endregion

        $this->info('Syncing transactions...');
        $this->syncTransactionSummaries($branches);

        // #region agent log
        $__dbg('H3', 'transactions finished', []);
        // #endregion

        $this->info('Syncing footfall (all branches, one request)...');
        $this->syncAllFootfall($branches, $start, $end);

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
                    // #region agent log
                    file_put_contents(base_path('debug-741ebe.log'), json_encode([
                        'sessionId' => '741ebe',
                        'runId' => 'post-fix',
                        'hypothesisId' => 'H1',
                        'location' => 'SyncThirdPartyData.php:fetchSalesForDateRange',
                        'message' => 'sales API success',
                        'data' => [
                            'from' => $params['SaleFromDate'],
                            'to' => $params['SaleToDate'],
                            'count' => is_array($salesList) ? count($salesList) : -1,
                            'attempt' => $attempt,
                        ],
                        'timestamp' => (int) round(microtime(true) * 1000),
                    ]) . "\n", FILE_APPEND);
                    // #endregion
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
        // $lineItemNames = [];

        foreach ($branchesByName as $shopName => $branch) {
            $shopSales = $salesByShop[$shopName] ?? [];
            if (empty($shopSales)) {
                continue;
            }

            $this->saveBranchSales($shopSales);
            // $lineItemNames = array_merge($lineItemNames, $this->saveBranchSales($shopSales));
            $count = count($shopSales);
            $saved += $count;
            $this->info("Sales saved: {$shopName} ({$count} invoices)");
        }

        // if (!empty($lineItemNames)) {
        //     app(LineItemSyncService::class)->syncFromNames($lineItemNames);
        // }

        return $saved;
    }

    /**
     * @return array<int, string|null> product names for line-item sync
     */
    private function saveBranchSales(array $sales): void
    {
        // $lineItemNames = [];

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
                    'sale_key' => ($sale['ShopId'] ?? '') . '|' . ($sale['InvoiceNo'] ?? ''),
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
                        'shop_name' => $sale['ShopName'] ?? null,
                        'sale_key' => ($sale['ShopId'] ?? '') . '|' . ($sale['InvoiceNo'] ?? ''),
                    ]
                );

                // $lineItemNames[] = $item['ProductName'] ?? null;
            }
        }

        // return $lineItemNames;
    }

    private function syncTransactionSummaries($branches): void
{
    /*
     * Keep concurrency low because the Yofi API has already returned
     * HTTP 429 (Too Many Requests).
     */
    $chunkSize = 3;

    /*
     * Maximum number of attempts per branch.
     *
     * Attempt 1 = initial request
     * Attempt 2 = retry
     * Attempt 3 = retry
     * Attempt 4 = final retry
     */
    $maxAttempts = 4;

    /*
     * Base exponential backoff:
     *
     * Attempt 1 -> no delay
     * Attempt 2 -> 2 seconds
     * Attempt 3 -> 4 seconds
     * Attempt 4 -> 8 seconds
     *
     * A small random jitter is added to prevent all requests
     * retrying at exactly the same time.
     */
    $baseBackoffSeconds = 2;

    foreach ($branches->chunk($chunkSize) as $chunk) {

        $responses = [];

        /*
         * Process a small number of branches concurrently.
         */
        try {
            $responses = Http::pool(function ($pool) use ($chunk) {
                $requests = [];

                foreach ($chunk as $branch) {
                    $branchKey = $branch->id;

                    $requests["txn_{$branchKey}"] = $pool
                        ->as("txn_{$branchKey}")
                        ->withOptions([
                            'connect_timeout' => 15,
                            'timeout' => 60,
                        ])
                        ->withHeaders([
                            'Authorization' => 'Token ' . $this->yofiToken,
                            'Accept' => 'application/json',
                        ])
                        ->get(
                            'https://unov.yofi.link/api/outlet/' . $branch->branch_id . '/'
                        );
                }

                return $requests;
            });
        } catch (\Throwable $e) {

            /*
             * A pool-level failure should not stop the complete
             * sales/footfall/transaction synchronization.
             */
            Log::warning('Transaction pool request failed', [
                'error' => $e->getMessage(),
            ]);

            $responses = [];
        }

        foreach ($chunk as $branch) {

            $branchKey = $branch->id;
            $response = $responses["txn_{$branchKey}"] ?? null;

            /*
             * If the pooled response is missing or malformed,
             * fall back to an individual request with retries.
             *
             * This also protects against:
             *
             * Call to a member function getStatusCode() on null
             */
            if (!$this->isValidHttpResponse($response)) {

                $response = $this->fetchTransactionWithRetry(
                    $branch,
                    $maxAttempts,
                    $baseBackoffSeconds
                );
            }

            /*
             * Still no usable response after retries.
             */
            if (!$this->isValidHttpResponse($response)) {

                Log::warning('Transaction sync failed after retries', [
                    'branch_id' => $branch->id,
                    'outlet' => $branch->branch_id,
                    'status' => null,
                ]);

                continue;
            }

            /*
             * HTTP 429 from the pooled request.
             *
             * Retry individually with exponential backoff.
             */
            if ($response->status() === 429) {

                Log::warning('Transaction API rate limited', [
                    'branch_id' => $branch->id,
                    'outlet' => $branch->branch_id,
                    'status' => 429,
                ]);

                $response = $this->fetchTransactionWithRetry(
                    $branch,
                    $maxAttempts,
                    $baseBackoffSeconds
                );
            }

            /*
             * Save the final response.
             *
             * saveTransactionSummary() already checks
             * successful(), but only call it when the response
             * itself is safe.
             */
            if ($this->isValidHttpResponse($response)) {

                $this->saveTransactionSummary(
                    $branch,
                    $response
                );
            } else {

                Log::warning('Transaction sync skipped - invalid response', [
                    'branch_id' => $branch->id,
                    'outlet' => $branch->branch_id,
                ]);
            }
        }

        /*
         * Small pause between chunks to reduce pressure on
         * the Yofi API.
         */
        usleep(500000); // 0.5 second
    }
}


/**
 * Fetch a transaction summary for one branch with:
 *
 * - 429 retry
 * - exponential backoff
 * - Retry-After support
 * - connection timeout handling
 * - request timeout handling
 * - safe exception handling
 */
private function fetchTransactionWithRetry(
    $branch,
    int $maxAttempts = 4,
    int $baseBackoffSeconds = 2
) {
    $url = 'https://unov.yofi.link/api/outlet/' . $branch->branch_id . '/';

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

        try {

            $this->info(
                "Transaction sync: {$branch->branch_id} - attempt {$attempt}/{$maxAttempts}"
            );

            $response = Http::withOptions([
                    'connect_timeout' => 15,
                    'timeout' => 60,
                ])
                ->withHeaders([
                    'Authorization' => 'Token ' . $this->yofiToken,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            /*
             * Protect against the same invalid Response object
             * that caused:
             *
             * getStatusCode() on null
             */
            if (!$this->isValidHttpResponse($response)) {

                Log::warning('Transaction API returned invalid response', [
                    'branch_id' => $branch->id,
                    'outlet' => $branch->branch_id,
                    'attempt' => $attempt,
                ]);

                if ($attempt < $maxAttempts) {

                    $delay = $this->calculateExponentialBackoff(
                        $attempt,
                        $baseBackoffSeconds
                    );

                    $this->info(
                        "Retrying {$branch->branch_id} in {$delay} seconds..."
                    );

                    sleep($delay);

                    continue;
                }

                return null;
            }

            $status = $response->status();

            /*
             * Success.
             */
            if ($response->successful()) {

                return $response;
            }

            /*
             * Rate limited.
             */
            if ($status === 429) {

                if ($attempt >= $maxAttempts) {

                    Log::warning('Transaction API 429 - retries exhausted', [
                        'branch_id' => $branch->id,
                        'outlet' => $branch->branch_id,
                        'attempt' => $attempt,
                    ]);

                    return $response;
                }

                /*
                 * Prefer Retry-After when Yofi provides it.
                 */
                $retryAfter = $response->header('Retry-After');

                if (is_numeric($retryAfter)) {

                    $delay = max(1, (int) $retryAfter);

                } else {

                    $delay = $this->calculateExponentialBackoff(
                        $attempt,
                        $baseBackoffSeconds
                    );
                }

                Log::warning('Transaction API rate limited - retrying', [
                    'branch_id' => $branch->id,
                    'outlet' => $branch->branch_id,
                    'status' => 429,
                    'attempt' => $attempt,
                    'retry_after' => $retryAfter,
                    'delay_seconds' => $delay,
                ]);

                $this->info(
                    "429 for {$branch->branch_id}. Retrying in {$delay} seconds..."
                );

                sleep($delay);

                continue;
            }

            /*
             * Server-side errors.
             *
             * Retry 5xx responses because these may be temporary.
             */
            if ($status >= 500 && $status <= 599) {

                if ($attempt >= $maxAttempts) {

                    Log::warning('Transaction API server error - retries exhausted', [
                        'branch_id' => $branch->id,
                        'outlet' => $branch->branch_id,
                        'status' => $status,
                        'attempt' => $attempt,
                    ]);

                    return $response;
                }

                $delay = $this->calculateExponentialBackoff(
                    $attempt,
                    $baseBackoffSeconds
                );

                Log::warning('Transaction API server error - retrying', [
                    'branch_id' => $branch->id,
                    'outlet' => $branch->branch_id,
                    'status' => $status,
                    'attempt' => $attempt,
                    'delay_seconds' => $delay,
                ]);

                sleep($delay);

                continue;
            }

            /*
             * Other HTTP errors such as 400, 401, 403, 404 should
             * not be retried because they are generally not temporary.
             */
            Log::warning('Transaction API request failed', [
                'branch_id' => $branch->id,
                'outlet' => $branch->branch_id,
                'status' => $status,
                'attempt' => $attempt,
            ]);

            return $response;

        } catch (ConnectionException $e) {

            /*
             * Handles connection timeout / connection failure.
             */
            Log::warning('Transaction API connection failed', [
                'branch_id' => $branch->id,
                'outlet' => $branch->branch_id,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);

            if ($attempt >= $maxAttempts) {
                return null;
            }

            $delay = $this->calculateExponentialBackoff(
                $attempt,
                $baseBackoffSeconds
            );

            $this->info(
                "Connection failed for {$branch->branch_id}. " .
                "Retrying in {$delay} seconds..."
            );

            sleep($delay);

        } catch (\Throwable $e) {

            /*
             * Catch unexpected HTTP/client errors so one branch
             * cannot terminate the entire scheduled command.
             */
            Log::warning('Transaction API unexpected error', [
                'branch_id' => $branch->id,
                'outlet' => $branch->branch_id,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);

            if ($attempt >= $maxAttempts) {
                return null;
            }

            $delay = $this->calculateExponentialBackoff(
                $attempt,
                $baseBackoffSeconds
            );

            sleep($delay);
        }
    }

    return null;
}


/**
 * Safely determine whether the Laravel HTTP response can be used.
 *
 * This is specifically intended to prevent:
 *
 * Call to a member function getStatusCode() on null
 */
private function isValidHttpResponse($response): bool
{
    if (!$response instanceof \Illuminate\Http\Client\Response) {
        return false;
    }

    try {
        /*
         * Calling status() inside try/catch verifies that the
         * wrapped PSR response actually exists.
         */
        $response->status();

        return true;

    } catch (\Throwable $e) {

        return false;
    }
}


/**
 * Exponential backoff with small jitter.
 *
 * attempt 1 -> 2 + jitter
 * attempt 2 -> 4 + jitter
 * attempt 3 -> 8 + jitter
 */
private function calculateExponentialBackoff(
    int $attempt,
    int $baseSeconds = 2
): int {
    $exponential = $baseSeconds * (2 ** max(0, $attempt - 1));

    /*
     * Random 0-1 second jitter.
     */
    $jitter = random_int(0, 1);

    /*
     * Maximum delay = 30 seconds.
     */
    return min($exponential + $jitter, 30);
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
