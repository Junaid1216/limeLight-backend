<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\Sale;
use App\Models\SaleStaff;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesHistoryController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'day');
        $role = $request->get('role', '');
        $id = $request->get('id');

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
        if ($period === 'week') {
            return [
                Carbon::now()->startOfWeek()->startOfDay(),
                Carbon::now()->endOfWeek()->endOfDay(),
            ];
        }

        if ($period === 'month') {
            return [
                Carbon::now()->startOfMonth()->startOfDay(),
                Carbon::now()->endOfMonth()->endOfDay(),
            ];
        }

        return [
            Carbon::today()->startOfDay(),
            Carbon::today()->endOfDay(),
        ];
    }

    private function asmSales(AreaSaleManager $asm, Carbon $from, Carbon $to): array
    {
        $branches = Branch::where('region_id', $asm->region_id)->get();
        $branchNames = $branches->pluck('name')->filter()->values();

        if ($branchNames->isEmpty()) {
            return [[], ['invoices' => 0, 'total_sales' => 0, 'quantity' => 0]];
        }

        $sales = Sale::with('items')
            ->whereIn('shop_name', $branchNames)
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')
            ->get();

        $rows = [];
        $summary = ['invoices' => 0, 'total_sales' => 0, 'quantity' => 0];

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
                'salesperson' => $staffNames ?: '-',
                'salesperson_code' => $staffCodes ?: '-',
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
            return [[], ['invoices' => 0, 'total_sales' => 0, 'quantity' => 0]];
        }

        $sales = Sale::with('items')
            ->where('shop_name', $branch->name)
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')
            ->get();

        $rows = [];
        $summary = ['invoices' => 0, 'total_sales' => 0, 'quantity' => 0];

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
                'salesperson' => $staffNames ?: '-',
                'salesperson_code' => $staffCodes ?: '-',
                'quantity' => $qty,
                'amount' => (float) $sale->net_total,
            ];

            $summary['invoices']++;
            $summary['total_sales'] += (float) $sale->net_total;
            $summary['quantity'] += $qty;
        }

        return [$rows, $summary];
    }

    private function saleStaffSales(SaleStaff $staff, Carbon $from, Carbon $to): array
    {
        if (!$staff->employee_id) {
            return [[], ['invoices' => 0, 'total_sales' => 0, 'quantity' => 0]];
        }

        $sales = Sale::with(['items' => function ($q) use ($staff) {
            $q->where('salesperson_code', $staff->employee_id);
        }])
            ->whereHas('items', function ($q) use ($staff) {
                $q->where('salesperson_code', $staff->employee_id);
            })
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')
            ->get();

        $rows = [];
        $summary = ['invoices' => 0, 'total_sales' => 0, 'quantity' => 0];

        foreach ($sales as $sale) {
            $qty = $sale->items->sum(function ($item) {
                return max(0, (int) $item->quantity);
            });
            $amount = $sale->items->sum(function ($item) {
                return (float) $item->price * max(0, (int) $item->quantity);
            });

            $rows[] = [
                'invoice_id' => $sale->invoice_id,
                'branch' => $sale->shop_name,
                'date' => $sale->date,
                'salesperson' => $staff->name,
                'salesperson_code' => $staff->employee_id,
                'quantity' => $qty,
                'amount' => $amount,
            ];

            $summary['invoices']++;
            $summary['total_sales'] += $amount;
            $summary['quantity'] += $qty;
        }

        return [$rows, $summary];
    }
}
