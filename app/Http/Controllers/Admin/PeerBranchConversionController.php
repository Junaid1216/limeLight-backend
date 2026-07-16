<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\FootfallDailySummary;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PeerBranchConversionController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'weekly');
        $id = $request->get('id');

        [$from, $to] = $this->resolvePeriod($period);

        $branchManagers = BranchManager::with('branch')->orderBy('name')->get();

        $branchManagerOptions = $branchManagers->map(function ($m) {
            return ['id' => $m->id, 'label' => $m->id . ' - ' . $m->name];
        })->values();

        $selected = null;
        $rows = [];
        $yourBranch = null;

        if ($id) {
            $selected = $branchManagers->firstWhere('id', (int) $id);

            if ($selected && $selected->branch) {
                $peerBranches = Branch::where('region_id', $selected->branch->region_id)->get();
                $rows = $this->branchConversionRows($peerBranches, $from, $to);
                $yourBranch = collect($rows)->firstWhere('branch_id', $selected->branch->id);
            }
        }

        return view('admin.peerbranchconversion.index', compact(
            'period',
            'id',
            'from',
            'to',
            'branchManagerOptions',
            'selected',
            'rows',
            'yourBranch'
        ));
    }

    private function resolvePeriod(string $period): array
    {
        if ($period === 'monthly') {
            return [
                Carbon::now()->startOfMonth()->startOfDay(),
                Carbon::now()->endOfMonth()->endOfDay(),
            ];
        }

        return [
            Carbon::today()->subDays(6)->startOfDay(),
            Carbon::today()->endOfDay(),
        ];
    }

    private function branchConversionRows($branches, Carbon $from, Carbon $to): array
    {
        $rows = [];

        foreach ($branches as $branch) {
            $traffic = FootfallDailySummary::where('branch_id', $branch->id)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->sum('footfall');

            $invoices = Sale::where('shop_name', $branch->name)
                ->whereBetween('date', [$from, $to])
                ->count();

            $conversion = $traffic > 0
                ? round(($invoices / $traffic) * 100, 2)
                : 0;

            $rows[] = [
                'branch_id' => $branch->id,
                'branch' => $branch->name,
                'traffic' => $traffic,
                'invoices' => $invoices,
                'conversion_percentage' => $conversion,
            ];
        }

        usort($rows, function ($a, $b) {
            return $b['conversion_percentage'] <=> $a['conversion_percentage'];
        });

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }

        return $rows;
    }
}
