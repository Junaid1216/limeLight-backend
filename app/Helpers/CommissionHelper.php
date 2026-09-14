<?php

namespace App\Helpers;

use App\Models\BranchManager;
use App\Models\Commission;
use App\Models\CommissionHistory;
use Carbon\Carbon;

class CommissionHelper
{
    /**
     * Designation display name (lowercase) → commissions.role key.
     * Must stay in sync with admin Commissions form options.
     */
    private static $designationRoleMap = [
        'branch manager sales - a' => 'branch_manager_sales_a',
        'branch manager sales - b' => 'branch_manager_sales_b',
        'branch manager sales - c' => 'branch_manager_sales_c',
        'branch manager sales - d' => 'branch_manager_sales_d',
        'branch manager sales - e' => 'branch_manager_sales_e',
        'sales staff' => 'sales_staff',
        'assistant branch manager' => 'assistant_branch_manager',
        'inventory manager - a' => 'inventory_manager_a',
        'inventory manager - b' => 'inventory_manager_b',
        'inventory manager - c' => 'inventory_manager_c',
        // Legacy catch-all designation / role
        'branch manager' => 'branch_manager',
    ];

    /**
     * Role key → human label for admin lists.
     */
    private static $roleLabels = [
        'branch_manager_sales_a' => 'Branch Manager Sales - A',
        'branch_manager_sales_b' => 'Branch Manager Sales - B',
        'branch_manager_sales_c' => 'Branch Manager Sales - C',
        'branch_manager_sales_d' => 'Branch Manager Sales - D',
        'branch_manager_sales_e' => 'Branch Manager Sales - E',
        'sales_staff' => 'Sales Staff',
        'assistant_branch_manager' => 'Assistant Branch Manager',
        'inventory_manager_a' => 'Inventory Manager - A',
        'inventory_manager_b' => 'Inventory Manager - B',
        'inventory_manager_c' => 'Inventory Manager - C',
        'branch_manager' => 'Branch Manager',
    ];

    /**
     * Map a designation name to a commissions.role key.
     */
    public static function roleKeyFromDesignationName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $key = strtolower(trim($name));
        if ($key === '' || $key === 'area sales manager') {
            return null;
        }

        return self::$designationRoleMap[$key] ?? null;
    }

    /**
     * Human-readable label for a stored commission role key.
     */
    public static function labelForRole(?string $role): string
    {
        if ($role === null || $role === '') {
            return '-';
        }

        return self::$roleLabels[$role] ?? $role;
    }

    /**
     * Whether any commission rate exists for this role (history or current row).
     */
    public static function hasRate(string $role, $at = null): bool
    {
        $at = $at ? Carbon::parse($at) : Carbon::now();

        $historyExists = CommissionHistory::query()
            ->where('role', $role)
            ->where('effective_from', '<=', $at)
            ->where(function ($q) use ($at) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>', $at);
            })
            ->exists();

        if ($historyExists) {
            return true;
        }

        return Commission::where('role', $role)->whereNotNull('commission')->exists();
    }

    /**
     * Rate (%) for a branch manager based on their designation.
     * Falls back to legacy `branch_manager` when designation is missing
     * or no rate is configured for the mapped designation key.
     */
    public static function rateForBranchManager(BranchManager $manager, $at = null): float
    {
        $manager->loadMissing('designation');
        $roleKey = self::roleKeyFromDesignationName(optional($manager->designation)->name);

        if ($roleKey && self::hasRate($roleKey, $at)) {
            return self::rateFor($roleKey, $at);
        }

        return self::rateFor('branch_manager', $at);
    }
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
