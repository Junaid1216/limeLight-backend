<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\AssignedTarget;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\Commission;
use App\Models\FootfallDailySummary;
use App\Models\Region;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleStaff;
use App\Models\Target;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    private $categories = ['garments', 'unstitched', 'accessories'];

    private $garmentCategories = [
        'signature', 'flowy', 'trouser', 'regular prints', 'fusion co-ords', 'festive',
        'composed rotary', 'premium', 'casual', 'glam', 'dailywear', 'regular running',
        'regular panel', 'modish', 'trendy', 'premium wear', 'tops',
    ];

    private $unstitchedCategories = [
        'dupatta - dyed', 'unstitched trousers',
    ];

    private $accessoryCategories = [
        'hand bag', 'scarves - printed', 'sunglasses', 'jewellery', 'clutches',
        'perfumes', 'body mist', 'non-tradable',
    ];

    public function index(Request $request)
    {
        $period = $request->get('period', 'weekly');
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

        $selected = null;
        $comparisons = null;

        if ($id && $role) {
            if ($role === 'asm') {
                $selected = $asms->firstWhere('id', (int) $id);
                if ($selected) {
                    $comparisons = $this->asmComparisons($selected, $from, $to);
                }
            } elseif ($role === 'branch_manager') {
                $selected = $branchManagers->firstWhere('id', (int) $id);
                if ($selected) {
                    $selected->load('branch');
                    $comparisons = $this->branchManagerComparisons($selected, $from, $to);
                }
            } elseif ($role === 'sale_staff') {
                $selected = $saleStaff->firstWhere('id', (int) $id);
                if ($selected) {
                    $selected->load('branch');
                    $comparisons = $this->saleStaffComparisons($selected, $from, $to);
                }
            }
        }

        return view('admin.reporting.index', compact(
            'period',
            'role',
            'id',
            'from',
            'to',
            'asmOptions',
            'branchManagerOptions',
            'saleStaffOptions',
            'selected',
            'comparisons'
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

        // weekly (default) — last 7 days, matching app
        return [
            Carbon::today()->subDays(6)->startOfDay(),
            Carbon::today()->endOfDay(),
        ];
    }

    private function asmComparisons(AreaSaleManager $asm, Carbon $from, Carbon $to): array
    {
        $branches = Branch::where('region_id', $asm->region_id)->get();

        return [
            'type' => 'asm',
            'branch_conversion' => $this->branchConversionRows($branches, $from, $to),
            'branch_category' => $this->branchCategoryRows($branches),
            'region_conversion' => $this->regionConversionRows($asm->region_id, $from, $to),
            'staff_comparison' => $this->staffComparisonByBranches($branches),
        ];
    }

    private function branchManagerComparisons(BranchManager $manager, Carbon $from, Carbon $to): array
    {
        $branch = $manager->branch;

        if (!$branch) {
            return [
                'type' => 'branch_manager',
                'summary' => null,
                'staff_comparison' => [],
                'branch_category' => [],
                'branch_conversion' => [],
            ];
        }

        $peerBranches = Branch::where('region_id', $branch->region_id)->get();
        $month = Carbon::now()->format('F');
        $year = Carbon::now()->year;

        $monthlyTarget = Target::where('branch_id', $branch->id)
            ->where('month', $month)
            ->where('year', $year)
            ->sum('monthly_target');

        $achieved = Sale::where('shop_name', $branch->name)
            ->whereBetween('date', [$from, $to])
            ->sum('net_total');

        $achievedPercentage = $monthlyTarget > 0
            ? min(100, round(($achieved / $monthlyTarget) * 100, 2))
            : 0;

        return [
            'type' => 'branch_manager',
            'summary' => [
                'branch' => $branch->name,
                'monthly_target' => $monthlyTarget,
                'achieved' => $achieved,
                'remaining' => max($monthlyTarget - $achieved, 0),
                'achieved_percentage' => $achievedPercentage,
                'remaining_percentage' => 100 - $achievedPercentage,
                'commission' => round($achieved * 0.05, 2),
            ],
            'staff_comparison' => $this->staffComparisonForBranch($branch),
            'branch_category' => $this->peerBranchCategoryRows($peerBranches, $branch->id),
            'branch_conversion' => $this->branchConversionRows($peerBranches, $from, $to),
        ];
    }

    private function saleStaffComparisons(SaleStaff $staff, Carbon $from, Carbon $to): array
    {
        $targets = AssignedTarget::where('user_id', $staff->id)->get();
        $assigned = ['garments' => 0, 'unstitched' => 0, 'accessories' => 0];

        foreach ($targets as $target) {
            $key = strtolower($target->category);
            if (isset($assigned[$key])) {
                $assigned[$key] = $target->target;
            }
        }

        $sold = ['garments' => 0, 'unstitched' => 0, 'accessories' => 0];
        $totalQty = 0;

        $saleItems = SaleItem::where('salesperson_code', $staff->employee_id)
            ->whereHas('sale', function ($q) use ($from, $to) {
                $q->whereBetween('date', [$from, $to]);
            })
            ->get();

        foreach ($saleItems as $item) {
            $qty = max(0, (int) $item->quantity);
            $totalQty += $qty;
            $bucket = $this->mapCategoryBucket(strtolower(trim($item->category ?? '')));
            if ($bucket) {
                $sold[$bucket] += $qty;
            }
        }

        $targetVsAchievement = [];
        foreach ($assigned as $category => $target) {
            $achieved = $sold[$category];
            $percentage = $target > 0 ? min(100, round(($achieved / $target) * 100)) : 0;
            $targetVsAchievement[] = [
                'category' => ucfirst($category),
                'target' => $target,
                'achieved' => $achieved,
                'achieved_percentage' => $percentage,
                'remaining_percentage' => 100 - $percentage,
            ];
        }

        $totalTarget = array_sum($assigned);
        $overallPercentage = $totalTarget > 0
            ? min(100, round(($totalQty / $totalTarget) * 100))
            : 0;

        $conversionChart = [];
        $peak = null;

        if ($staff->branch) {
            $cursor = $from->copy()->startOfDay();
            $end = $to->copy()->startOfDay();

            while ($cursor->lte($end)) {
                $day = $cursor->toDateString();
                $footfall = FootfallDailySummary::where('branch_id', $staff->branch->id)
                    ->whereDate('date', $day)
                    ->sum('footfall');
                $invoices = Sale::where('shop_name', $staff->branch->name)
                    ->whereDate('date', $day)
                    ->count();
                $rate = $footfall > 0 ? round(($invoices / $footfall) * 100, 2) : 0;

                $row = [
                    'date' => $day,
                    'footfall' => $footfall,
                    'invoices' => $invoices,
                    'conversion_rate' => $rate,
                ];
                $conversionChart[] = $row;

                if ($peak === null || $rate > $peak['conversion_rate']) {
                    $peak = $row;
                }

                $cursor->addDay();
            }
        }

        return [
            'type' => 'sale_staff',
            'summary' => [
                'branch' => optional($staff->branch)->name,
                'target' => $totalTarget,
                'achieved' => $totalQty,
                'achieved_percentage' => $overallPercentage,
                'remaining_percentage' => 100 - $overallPercentage,
            ],
            'target_vs_achievement' => $targetVsAchievement,
            'conversion_chart' => $conversionChart,
            'peak' => $peak,
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

        return $this->rankBy($rows, 'conversion_percentage');
    }

    private function branchCategoryRows($branches): array
    {
        $month = Carbon::now()->format('F');
        $year = Carbon::now()->year;
        $response = [];

        foreach ($this->categories as $category) {
            $rows = [];

            foreach ($branches as $branch) {
                $target = Target::where('branch_id', $branch->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->where('category', $category)
                    ->value('monthly_target') ?? 0;

                $achieved = $this->achievedQtyForBranch($branch->name, $category);
                $percentage = $target > 0 ? min(100, round(($achieved / $target) * 100)) : 0;

                $rows[] = [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'target' => $target,
                    'achieved' => $achieved,
                    'achievement_percentage' => $percentage,
                    'remaining_percentage' => 100 - $percentage,
                ];
            }

            $response[] = [
                'category' => ucfirst($category),
                'branches' => $this->rankBy($rows, 'achievement_percentage'),
            ];
        }

        return $response;
    }

    private function peerBranchCategoryRows($branches, int $yourBranchId): array
    {
        $month = Carbon::now()->format('F');
        $year = Carbon::now()->year;
        $response = [];

        foreach ($this->categories as $category) {
            $rows = [];

            foreach ($branches as $branchItem) {
                $target = Target::where('branch_id', $branchItem->id)
                    ->where('category', $category)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->value('monthly_target') ?? 0;

                $achieved = $this->achievedQtyForBranch($branchItem->name, $category);
                $achievement = $target > 0 ? min(100, round(($achieved / $target) * 100)) : 0;

                $rows[] = [
                    'branch_id' => $branchItem->id,
                    'branch' => $branchItem->name,
                    'target' => $target,
                    'achieved' => $achieved,
                    'achievement' => $achievement,
                    'remaining' => 100 - $achievement,
                ];
            }

            $ranked = $this->rankBy($rows, 'achievement');
            $yourBranch = collect($ranked)->firstWhere('branch_id', $yourBranchId);
            $otherBranches = collect($ranked)->where('branch_id', '!=', $yourBranchId)->values()->all();

            $response[] = [
                'category' => ucfirst($category),
                'your_branch' => $yourBranch,
                'branches' => $otherBranches,
            ];
        }

        return $response;
    }

    private function regionConversionRows(int $yourRegionId, Carbon $from, Carbon $to): array
    {
        $rows = [];

        foreach (Region::all() as $region) {
            $branchIds = Branch::where('region_id', $region->id)->pluck('id');
            $branchNames = Branch::whereIn('id', $branchIds)->pluck('name');

            $traffic = FootfallDailySummary::whereIn('branch_id', $branchIds)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->sum('footfall');

            $invoices = Sale::whereIn('shop_name', $branchNames)
                ->whereBetween('date', [$from, $to])
                ->count();

            $conversion = $traffic > 0
                ? round(($invoices / $traffic) * 100, 2)
                : 0;

            $rows[] = [
                'region_id' => $region->id,
                'region' => $region->name,
                'traffic' => $traffic,
                'invoices' => $invoices,
                'conversion_percentage' => $conversion,
            ];
        }

        $ranked = $this->rankBy($rows, 'conversion_percentage');

        return [
            'your_region' => collect($ranked)->firstWhere('region_id', $yourRegionId),
            'regions' => collect($ranked)->where('region_id', '!=', $yourRegionId)->values()->all(),
            'all' => $ranked,
        ];
    }

    private function staffComparisonByBranches($branches): array
    {
        $response = [];

        foreach ($branches as $branch) {
            $response[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'staff' => $this->staffComparisonForBranch($branch),
            ];
        }

        return $response;
    }

    private function staffComparisonForBranch(Branch $branch): array
    {
        $commissionRate = Commission::where('role', 'sales_staff')->value('commission') ?? 0;
        $staffList = SaleStaff::where('branch_id', $branch->id)->get();
        $staffData = [];

        foreach ($staffList as $staff) {
            $target = AssignedTarget::where('user_id', $staff->id)->sum('target');
            $achieved = SaleItem::where('salesperson_code', $staff->employee_id)->sum('quantity');
            $saleAmount = SaleItem::where('salesperson_code', $staff->employee_id)
                ->selectRaw('SUM(quantity * price) as total')
                ->value('total') ?? 0;

            $percentage = $target > 0 ? min(100, round(($achieved / $target) * 100)) : 0;

            $staffData[] = [
                'staff_id' => $staff->id,
                'name' => $staff->name,
                'target' => $target,
                'achieved' => $achieved,
                'remaining' => max($target - $achieved, 0),
                'achievement_percentage' => $percentage,
                'remaining_percentage' => 100 - $percentage,
                'commission' => round(($saleAmount * $commissionRate) / 100, 2),
            ];
        }

        return $this->rankBy($staffData, 'achievement_percentage');
    }

    private function achievedQtyForBranch(string $branchName, string $category): int
    {
        $achieved = 0;
        $items = SaleItem::whereHas('sale', function ($q) use ($branchName) {
            $q->where('shop_name', $branchName);
        })->get();

        foreach ($items as $item) {
            $bucket = $this->mapCategoryBucket(strtolower(trim($item->category ?? '')));
            if ($bucket === $category) {
                $achieved += max(0, (int) $item->quantity);
            }
        }

        return $achieved;
    }

    private function mapCategoryBucket(string $itemCategory): ?string
    {
        if (in_array($itemCategory, $this->garmentCategories, true)) {
            return 'garments';
        }
        if (in_array($itemCategory, $this->unstitchedCategories, true)) {
            return 'unstitched';
        }
        if (in_array($itemCategory, $this->accessoryCategories, true)) {
            return 'accessories';
        }

        return null;
    }

    private function rankBy(array $rows, string $key): array
    {
        usort($rows, function ($a, $b) use ($key) {
            return $b[$key] <=> $a[$key];
        });

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }

        return $rows;
    }
}
