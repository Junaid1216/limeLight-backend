<?php

namespace App\Services;

use App\Models\LineItem;
use App\Models\SaleItem;

class LineItemSyncService
{
    /**
     * Sync unique product names from sale_items into line_items.
     * Existing names are skipped (case-insensitive, whitespace-normalized).
     * New rows stay Unassigned. Stale category-based rows are removed.
     */
    public function syncFromSaleItems(): int
    {
        $names = $this->collectProductNamesFromSaleItems();
        $created = $this->upsertNewNames($names);
        $this->removeNamesNotIn($names);

        return $created;
    }

    /**
     * Upsert unique product names from an API/sales batch.
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
     * Also normalizes remaining names (collapse whitespace).
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

        foreach ($groups as $lower => $group) {
            // Prefer a row that already has a category mapped; otherwise keep newest
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
     * @return array<string, string> lower => display
     */
    private function collectProductNamesFromSaleItems(): array
    {
        $names = [];

        $values = SaleItem::query()
            ->select('product_name')
            ->whereNotNull('product_name')
            ->where('product_name', '!=', '')
            ->distinct()
            ->pluck('product_name');

        foreach ($values as $raw) {
            $name = $this->normalize($raw);
            if ($name !== null) {
                $names[mb_strtolower($name)] = $name;
            }
        }

        // Keep special Non-Tradable row used by the UI
        $names['non-tradable'] = 'Non-Tradable';

        return $names;
    }

    /**
     * Drop line items that are not current product names (old category rows).
     *
     * @param  array<string, string>  $allowedNames
     */
    private function removeNamesNotIn(array $allowedNames): int
    {
        if (empty($allowedNames)) {
            return 0;
        }

        $deleted = 0;
        LineItem::query()
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(200, function ($items) use ($allowedNames, &$deleted) {
                $ids = [];
                foreach ($items as $item) {
                    $normalized = $this->normalize($item->name);
                    if ($normalized === null) {
                        $ids[] = $item->id;
                        continue;
                    }
                    if (!isset($allowedNames[mb_strtolower($normalized)])) {
                        $ids[] = $item->id;
                    }
                }
                if (!empty($ids)) {
                    $deleted += LineItem::whereIn('id', $ids)->delete();
                }
            });

        return $deleted;
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
