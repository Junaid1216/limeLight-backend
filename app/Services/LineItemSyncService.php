<?php

namespace App\Services;

use App\Models\LineItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineItemSyncService
{
    private const LINE_ITEMS_API_URL = 'http://202.141.241.251:96/api/Sales/GetSalesByDateV3';
    private const SALES_APP_ID = 10;
    private const SALES_APP_KEY = 'jgiDwu3HwlKgbS9qorWmsVzhJ4oP0s5j';
    private const CONNECT_TIMEOUT = 20;
    private const TIMEOUT = 300;
    private const MAX_ATTEMPTS = 3;
    private const RETRY_BASE_MS = 2000;

    /**
     * Fetch unique LineItem values from GetSalesByDateV3 for the date range
     * and upsert them into line_items. Existing category mappings are kept.
     *
     * @return array{created: int, unique: int, had_failure: bool}
     */
    public function syncFromV3Api(Carbon $start, Carbon $end): array
    {
        $names = [];
        $hadFailure = false;
        $period = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());

        foreach ($period as $day) {
            /** @var Carbon $day */
            $salesList = $this->fetchV3SalesForDateRange(
                $day->copy()->startOfDay(),
                $day->copy()->endOfDay()
            );

            if ($salesList === null) {
                $hadFailure = true;
                continue;
            }

            foreach ($salesList as $sale) {
                foreach (($sale['data'] ?? []) as $item) {
                    $name = $this->normalize($item['LineItem'] ?? null);
                    if ($name !== null) {
                        $names[mb_strtolower($name)] = $name;
                    }
                }
            }
        }

        // Always keep Non-Tradable for UI
        $names['non-tradable'] = 'Non-Tradable';

        $created = $this->upsertNewNames($names);

        return [
            'created' => $created,
            'unique' => count($names),
            'had_failure' => $hadFailure,
        ];
    }

    /**
     * Upsert unique LineItem names from an in-memory batch.
     *
     * @param  iterable<string|null>  $rawNames
     */
    public function syncFromNames(iterable $rawNames): int
    {
        $names = [];
        foreach ($rawNames as $raw) {
            $name = $this->normalize($raw);
            if ($name !== null) {
                $names[mb_strtolower($name)] = $name;
            }
        }

        return $this->upsertNewNames($names);
    }

    /**
     * Remove duplicate line_items (same normalized name), keeping the best row.
     */
    public function dedupeExisting(): int
    {
        $items = LineItem::query()
            ->orderByDesc('id')
            ->get(['id', 'name', 'category_id']);

        $groups = $items->groupBy(function ($item) {
            $normalized = $this->normalize($item->name);
            return $normalized !== null ? mb_strtolower($normalized) : '';
        })->filter(function ($_, $key) {
            return $key !== '';
        });

        $deleted = 0;

        foreach ($groups as $group) {
            $keep = $group->first(function ($item) {
                return !empty($item->category_id);
            }) ?? $group->first();

            $canonical = $this->normalize($keep->name) ?? $keep->name;
            if ($keep->name !== $canonical) {
                $keep->name = $canonical;
                $keep->save();
            }

            $idsToDelete = $group
                ->where('id', '!=', $keep->id)
                ->pluck('id')
                ->all();

            if (!empty($idsToDelete)) {
                $deleted += LineItem::whereIn('id', $idsToDelete)->delete();
            }
        }

        return $deleted;
    }

    /**
     * @return array|null SalesList on success, [] when empty, null on hard failure
     */
    private function fetchV3SalesForDateRange(Carbon $start, Carbon $end): ?array
    {
        $params = [
            'AppId' => self::SALES_APP_ID,
            'AppKey' => self::SALES_APP_KEY,
            'SaleFromDate' => $start->format('Y-m-d H:i:s'),
            'SaleToDate' => $end->format('Y-m-d H:i:s'),
        ];

        $lastError = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::withOptions([
                        'connect_timeout' => self::CONNECT_TIMEOUT,
                        'timeout' => self::TIMEOUT,
                    ])
                    ->get(self::LINE_ITEMS_API_URL, $params);

                if ($response->successful()) {
                    $salesList = $response->json('SalesList') ?? [];
                    return is_array($salesList) ? $salesList : [];
                }

                $lastError = 'HTTP ' . $response->status();
                if ($response->status() < 500 && $response->status() !== 429) {
                    break;
                }
            } catch (ConnectionException $e) {
                $lastError = $e->getMessage();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::error('Line items V3 API unexpected error', [
                    'attempt' => $attempt,
                    'error' => $lastError,
                ]);
                break;
            }

            if ($attempt < self::MAX_ATTEMPTS) {
                usleep(self::RETRY_BASE_MS * (2 ** ($attempt - 1)) * 1000);
            }
        }

        Log::warning('Line items V3 sync failed', [
            'from' => $params['SaleFromDate'],
            'to' => $params['SaleToDate'],
            'error' => $lastError,
        ]);

        return null;
    }

    /**
     * @param  array<string, string>  $names  lower => display
     */
    private function upsertNewNames(array $names): int
    {
        if (empty($names)) {
            return 0;
        }

        $existing = LineItem::query()
            ->get(['id', 'name'])
            ->mapWithKeys(function ($item) {
                $normalized = $this->normalize($item->name);
                if ($normalized === null) {
                    return [];
                }
                return [mb_strtolower($normalized) => true];
            });

        $created = 0;
        $now = now();

        foreach ($names as $lower => $displayName) {
            if ($existing->has($lower)) {
                continue;
            }

            LineItem::create([
                'name' => $displayName,
                'api_id' => null,
                'category_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $existing[$lower] = true;
            $created++;
        }

        return $created;
    }

    public function normalize($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $name = trim(preg_replace('/\s+/u', ' ', (string) $value));
        return $name === '' ? null : $name;
    }
}
