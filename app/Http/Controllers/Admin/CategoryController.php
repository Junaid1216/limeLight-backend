<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LineItem;
use App\Services\LineItemSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /** Auto-sync V3 line items at most once per this many minutes when opening the page */
    private const AUTO_SYNC_CACHE_MINUTES = 60;

    /** Manual sync looks back this many days */
    private const MANUAL_SYNC_DAYS = 7;

    public function index(LineItemSyncService $lineItemSync)
    {
        $this->autoSyncLineItems($lineItemSync);
        $lineItemSync->dedupeExisting();

        $lineItems = LineItem::with('category')
            // ->whereRaw('LOWER(TRIM(name)) NOT IN (?, ?, ?)', [
            //     'non-tradable',
            //     'jewellery',
            //     'tops',
            // ])
            ->orderByDesc('id')
            ->get()
            ->unique(function ($item) use ($lineItemSync) {
                $normalized = $lineItemSync->normalize($item->name);
                return $normalized !== null ? mb_strtolower($normalized) : (string) $item->id;
            })
            ->sortBy([
                function ($item) {
                    if (empty($item->category_id) || empty($item->category->name ?? null)) {
                        return '0_unassigned';
                    }
                    return '1_' . mb_strtolower($item->category->name);
                },
                function ($item) {
                    if (empty($item->category_id) || empty($item->category->name ?? null)) {
                        return sprintf('%012d', 999999999999 - (int) $item->id);
                    }
                    return mb_strtolower($item->name ?? '');
                },
            ])
            ->values();

        $categories = Category::all();

        return view('admin.category.index', compact('categories', 'lineItems'));
    }

    /**
     * Manual sync: pull last N days of LineItem values from GetSalesByDateV3.
     */
    public function sync(LineItemSyncService $lineItemSync)
    {
        $end = Carbon::today()->endOfDay();
        $start = Carbon::today()->subDays(self::MANUAL_SYNC_DAYS - 1)->startOfDay();

        $result = $lineItemSync->syncFromV3Api($start, $end);
        $lineItemSync->dedupeExisting();

        Cache::put('line_items_v3_auto_synced_at', now()->timestamp, now()->addDay());

        return back()->with(
            'success',
            'Line items synced from GetSalesByDateV3. '
            . $result['unique'] . ' unique LineItem value(s) found, '
            . $result['created'] . ' new row(s) added.'
            . ($result['had_failure'] ? ' Some days failed to fetch.' : '')
        );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        LineItem::findOrFail($id)->update([
            'category_id' => $request->category_id,
        ]);

        return back()->with('success', 'Updated Successfully');
    }

    /**
     * Lightweight automatic sync (today only), throttled so page loads stay responsive.
     */
    private function autoSyncLineItems(LineItemSyncService $lineItemSync): void
    {
        $cacheKey = 'line_items_v3_auto_synced_at';
        $lastSynced = Cache::get($cacheKey);
        $threshold = now()->subMinutes(self::AUTO_SYNC_CACHE_MINUTES)->timestamp;

        if ($lastSynced && (int) $lastSynced >= $threshold) {
            return;
        }

        try {
            $lineItemSync->syncFromV3Api(
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay()
            );
            Cache::put($cacheKey, now()->timestamp, now()->addDay());
        } catch (\Throwable $e) {
            Log::warning('Automatic line items V3 sync skipped/failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
