<?php

namespace App\Helpers;

use App\Models\Commission;
use App\Models\CommissionHistory;
use Carbon\Carbon;

class CommissionHelper
{
    /**
     * Rate (%) that was active at the given sale moment.
     * Admin rate changes only affect sales after the change time.
     */
    public static function rateFor(string $role, $at = null): float
    {
        $at = $at ? Carbon::parse($at) : Carbon::now();

        $history = CommissionHistory::query()
            ->where('role', $role)
            ->where('effective_from', '<=', $at)
            ->where(function ($q) use ($at) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $at);
            })
            ->orderByDesc('effective_from')
            ->first();

        if ($history) {
            return (float) $history->commission;
        }

        return (float) (Commission::where('role', $role)->value('commission') ?? 0);
    }

    /**
     * Commission for one product line: qty × price × (rate ÷ 100).
     */
    public static function forProduct(string $role, $quantity, $price, $at = null): float
    {
        $rate = self::rateFor($role, $at);

        return (max(0, (float) $quantity) * max(0, (float) $price) * $rate) / 100;
    }

    /**
     * Sum per-product commissions then round once.
     * Each item uses its own sale date rate when available.
     *
     * @param  iterable  $items  SaleItem models or arrays with quantity/price[/date]
     * @param  mixed  $fallbackDate  Used when item has no date / sale relation
     */
    public static function sumProducts(string $role, iterable $items, $fallbackDate = null): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            if (is_array($item)) {
                $qty = $item['quantity'] ?? 0;
                $price = $item['price'] ?? 0;
                $at = $item['date'] ?? $fallbackDate;
            } else {
                $qty = $item->quantity ?? 0;
                $price = $item->price ?? 0;
                $at = $fallbackDate;
                if ($at === null) {
                    $at = optional($item->sale)->date ?? ($item->date ?? null);
                }
            }

            $total += self::forProduct($role, $qty, $price, $at);
        }

        return round($total, 2);
    }

    /**
     * Close open period and open a new rate period (only if rate actually changed).
     */
    public static function recordChange(string $role, float $newRate): void
    {
        $now = Carbon::now();

        $open = CommissionHistory::where('role', $role)
            ->whereNull('effective_to')
            ->orderByDesc('effective_from')
            ->first();

        if ($open && (float) $open->commission === (float) $newRate) {
            return;
        }

        if ($open) {
            $open->effective_to = $now;
            $open->save();
        }

        CommissionHistory::create([
            'role' => $role,
            'commission' => $newRate,
            'effective_from' => $now,
            'effective_to' => null,
        ]);
    }

    public static function closeRole(string $role): void
    {
        CommissionHistory::where('role', $role)
            ->whereNull('effective_to')
            ->update(['effective_to' => Carbon::now()]);
    }
}
