<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CommissionHelper;
use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\Sale;
use App\Models\SaleStaff;
use App\Models\Slab;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesHistoryController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'weekly');
        $role = $request->get('role', '');
        $id = $request->get('id');

        // Backward-compatible aliases from old filters
        if (in_array($period, ['day', 'daily'], true)) {
            $period = 'daily';
        } elseif (in_array($period, ['week', 'weekly'], true)) {
            $period = 'weekly';
        } elseif (in_array($period, ['month', 'monthly'], true)) {
            $period = 'monthly';
        }

        [$from, $to] = $this->resolvePeriod($period);

        $asms = AreaSaleManager::orderBy('name')->get();
        $branchManagers = BranchManager::orderBy('name')->get();
        $saleStaff = SaleStaff::orderBy('name')->get();

        $asmOptions = $asms->map(function ($a) {
            return ['id' => $a->id, 'label' => $a->id . ' - ' . $a->name];
        })->values();

        $branchManagerOptions = $branchManagers->map(function ($m) {
            return ['id' => $m->id, 'label' => $m->id . ' - ' . $m->name];
        })->values();

        $saleStaffOptions = $saleStaff->map(function ($s) {
            return ['id' => $s->id, 'label' => $s->id . ' - ' . $s->name];
        })->values();

        $rows = [];
        $summary = [
            'invoices' => 0,
            'total_sales' => 0,
            'quantity' => 0,
            'commission' => 0,
            'slip_bound_incentive' => 0,
        ];
        $selected = null;

        if ($id) {
            if ($role === 'asm') {
                $selected = $asms->firstWhere('id', (int) $id);
                if ($selected) {
                    [$rows, $summary] = $this->asmSales($selected, $from, $to);
                }
            } elseif ($role === 'branch_manager') {
                $selected = $branchManagers->firstWhere('id', (int) $id);
                if ($selected) {
                    $selected->loadMissing('branch');
                    [$rows, $summary] = $this->branchManagerSales($selected, $from, $to);
                }
            } elseif ($role === 'sale_staff') {
                $selected = $saleStaff->firstWhere('id', (int) $id);
                if ($selected) {
                    [$rows, $summary] = $this->saleStaffSales($selected, $from, $to);
                }
            }
        }

        return view('admin.saleshistory.index', compact(
            'period',
            'role',
            'id',
            'from',
            'to',
            'asmOptions',
            'branchManagerOptions',
            'saleStaffOptions',
            'selected',
            'rows',
            'summary'
        ));
    }

    private function resolvePeriod(string $period): array
    {
        if ($period === 'daily') {
            return [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
            ];
        }

        if ($period === 'monthly') {
            return [
                Carbon::now()->startOfMonth()->startOfDay(),
                Carbon::now()->endOfMonth()->endOfDay(),
            ];
        }

        // weekly — current week within the current month (week1: 1–7, week2: 8–14, ...)
        return $this->currentWeekRangeOfMonth();
    }

    private function currentWeekRangeOfMonth(): array
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth()->startOfDay();
        $monthEnd = $now->copy()->endOfMonth()->endOfDay();

        for ($i = 1; $i <= 4; $i++) {
            $start = $monthStart->copy()->addDays(($i - 1) * 7)->startOfDay();
            $end = $start->copy()->addDays(6)->endOfDay();

            if ($i === 4 || $end->gt($monthEnd)) {
                $end = $monthEnd->copy()->endOfDay();
            }

            if ($now->between($start, $end)) {
                return [$start, $end];
            }
        }

        return [$monthStart, $monthEnd];
    }

    /**
     * sales.date is a string column — compare by DATE() so daily/weekly/monthly filters work.
     */
    private function applySaleDateFilter($query, Carbon $from, Carbon $to)
    {
        return $query->whereRaw('DATE(`date`) BETWEEN ? AND ?', [
            $from->toDateString(),
            $to->toDateString(),
        ]);
    }

    private function asmSales(AreaSaleManager $asm, Carbon $from, Carbon $to): array
    {
        $branches = Branch::where('region_id', $asm->region_id)->get();
        $branchNames = $branches->pluck('name')->filter()->values();

        if ($branchNames->isEmpty()) {
            return [[], $this->emptySummary()];
        }

        $sales = $this->applySaleDateFilter(
            Sale::with('items')->whereIn('shop_name', $branchNames),
            $from,
            $to
        )
            ->orderByDesc('date')
            ->get();

        $rows = [];
        $summary = $this->emptySummary();

        foreach ($sales as $sale) {
            $qty = $sale->items->sum(function ($item) {
                return max(0, (int) $item->quantity);
            });
            $staffNames = $sale->items->pluck('salesperson_name')->filter()->unique()->implode(', ');
            $staffCodes = $sale->items->pluck('salesperson_code')->filter()->unique()->implode(', ');

            $rows[] = [
                'invoice_id' => $sale->invoice_id,
                'branch' => $sale->shop_name,
                'date' => $sale->date,
                'salesperson' => $this->formatSalesperson($staffNames, $staffCodes),
                'quantity' => $qty,
                'amount' => (float) $sale->net_total,
            ];

            $summary['invoices']++;
            $summary['total_sales'] += (float) $sale->net_total;
            $summary['quantity'] += $qty;
        }

        return [$rows, $summary];
    }

    private function branchManagerSales(BranchManager $manager, Carbon $from, Carbon $to): array
    {
        $branch = $manager->branch;

        if (!$branch || !$branch->name) {
            return [[], $this->emptySummary()];
        }

        $sales = $this->applySaleDateFilter(
            Sale::with('items')->where('shop_name', $branch->name),
            $from,
            $to
        )
            ->orderByDesc('date')
            ->get();

        $rows = [];
        $summary = $this->emptySummary();

        foreach ($sales as $sale) {
            $qty = $sale->items->sum(function ($item) {
                return max(0, (int) $item->quantity);
            });
            $staffNames = $sale->items->pluck('salesperson_name')->filter()->unique()->implode(', ');
            $staffCodes = $sale->items->pluck('salesperson_code')->filter()->unique()->implode(', ');
            // Product sales amount (qty × price) — commission base
            $amount = $sale->items->sum(function ($item) {
                return max(0, (float) $item->price) * max(0, (int) $item->quantity);
            });
            // Per product × rate at sale date, then sum
            $commission = CommissionHelper::sumProducts('branch_manager', $sale->items, $sale->date);

            $rows[] = [
                'invoice_id' => $sale->invoice_id,
                'branch' => $sale->shop_name,
                'date' => $sale->date,
                'salesperson' => $this->formatSalesperson($staffNames, $staffCodes),
                'quantity' => $qty,
                'amount' => $amount,
                'commission' => $commission,
            ];

            $summary['invoices']++;
            $summary['total_sales'] += $amount;
            $summary['quantity'] += $qty;
            $summary['commission'] += $commission;
        }

        $summary['commission'] = round($summary['commission'], 2);

        return [$rows, $summary];
    }

    private function saleStaffSales(SaleStaff $staff, Carbon $from, Carbon $to): array
    {
        if (!$staff->employee_id) {
            return [[], $this->emptySummary()];
        }

        $sales = $this->applySaleDateFilter(
            Sale::with(['items' => function ($q) use ($staff) {
                $q->where('salesperson_code', $staff->employee_id);
            }])->whereHas('items', function ($q) use ($staff) {
                $q->where('salesperson_code', $staff->employee_id);
            }),
            $from,
            $to
        )
            ->orderByDesc('date')
            ->get();

        $rows = [];
        $summary = $this->emptySummary();

        foreach ($sales as $sale) {
            $qty = $sale->items->sum(function ($item) {
                return max(0, (int) $item->quantity);
            });
            $amount = $sale->items->sum(function ($item) {
                return (float) $item->price * max(0, (int) $item->quantity);
            });

            $commission = CommissionHelper::sumProducts('sales_staff', $sale->items, $sale->date);

            $slab = Slab::where('from_amount', '<=', $sale->net_total)
                ->where('to_amount', '>=', $sale->net_total)
                ->first();
            $slipBoundIncentive = (float) ($slab->incentive_amount ?? 0);

            $rows[] = [
                'invoice_id' => $sale->invoice_id,
                'branch' => $sale->shop_name,
                'date' => $sale->date,
                'salesperson' => $this->formatSalesperson($staff->name, $staff->employee_id),
                'quantity' => $qty,
                'amount' => $amount,
                'commission' => $commission,
                'slip_bound_incentive' => $slipBoundIncentive,
            ];

            $summary['invoices']++;
            $summary['total_sales'] += $amount;
            $summary['quantity'] += $qty;
            $summary['commission'] += $commission;
            $summary['slip_bound_incentive'] += $slipBoundIncentive;
        }

        $summary['commission'] = round($summary['commission'], 2);
        $summary['slip_bound_incentive'] = round($summary['slip_bound_incentive'], 2);

        return [$rows, $summary];
    }

    private function emptySummary(): array
    {
        return [
            'invoices' => 0,
            'total_sales' => 0,
            'quantity' => 0,
            'commission' => 0,
            'slip_bound_incentive' => 0,
        ];
    }

    private function formatSalesperson(?string $name, ?string $code): string
    {
        $name = trim((string) $name);
        $code = trim((string) $code);

        if ($name !== '' && $code !== '') {
            return $name . ' (' . $code . ')';
        }

        if ($name !== '') {
            return $name;
        }

        if ($code !== '') {
            return $code;
        }

        return '-';
    }
}
