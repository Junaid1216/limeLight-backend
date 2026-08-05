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
        $branchCategoryFilter = $request->get('branch_category_filter', 'overall');

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
                    $comparisons = $this->asmComparisons($selected, $from, $to, $period);
                }
            } elseif ($role === 'branch_manager') {
                $selected = $branchManagers->firstWhere('id', (int) $id);
                if ($selected) {
                    $selected->load('branch');
                    $comparisons = $this->branchManagerComparisons($selected, $from, $to, $branchCategoryFilter, $period);
                }
            } elseif ($role === 'sale_staff') {
                $selected = $saleStaff->firstWhere('id', (int) $id);
                if ($selected) {
                    $selected->load('branch');
                    $comparisons = $this->saleStaffComparisons($selected, $from, $to, $period);
                }
            }
        }

        return view('admin.reporting.index', compact(
            'period',
            'role',
            'id',
            'branchCategoryFilter',
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

    private function currentWeekOfMonth(): int
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        for ($i = 1; $i <= 4; $i++) {
            $start = $monthStart->copy()->addDays(($i - 1) * 7)->startOfDay();
            $end = $start->copy()->addDays(6)->endOfDay();

            if ($i === 4 || $end->gt($monthEnd)) {
                $end = $monthEnd->copy()->endOfDay();
            }

            if ($now->between($start, $end)) {
                return $i;
            }
        }

        return min(4, (int) ceil($now->day / 7));
    }

    private function currentMonthName(): string
    {
        return Carbon::now()->format('F');
    }

    private function currentYearValue()
    {
        return Carbon::now()->year;
    }

    /**
     * Branch category target for selected period.
     * Monthly = monthly_target
     * Weekly  = monthly_target * week_N% / 100
     */
    private function resolveBranchCategoryTarget(?Target $targetRecord, string $period): float
    {
        if (!$targetRecord) {
            return 0;
        }

        $monthly = (float) ($targetRecord->monthly_target ?? 0);

        if ($period !== 'weekly') {
            return $monthly;
        }

        $weekColumn = 'week_' . $this->currentWeekOfMonth();
        $weekPercent = (float) ($targetRecord->{$weekColumn} ?? 0);

        return round(($monthly * $weekPercent) / 100, 2);
    }

    private function findBranchTarget(int $branchId, string $category): ?Target
    {
        $month = $this->currentMonthName();
        $year = $this->currentYearValue();

        return Target::where('branch_id', $branchId)
            ->where('category', $category)
            ->where(function ($q) use ($month) {
                $q->where('month', $month)
                    ->orWhere('month', strtolower($month))
                    ->orWhere('month', ucfirst(strtolower($month)));
            })
            ->where(function ($q) use ($year) {
                $q->where('year', $year)->orWhere('year', (string) $year);
            })
            ->first();
    }

    private function getStaffMonthTargets(int $staffId)
    {
        $month = $this->currentMonthName();
        $year = $this->currentYearValue();
        $monthVariants = array_values(array_unique([
            $month,
            strtolower($month),
            ucfirst(strtolower($month)),
            Carbon::now()->format('m'),
            (string) Carbon::now()->month,
        ]));
        $yearVariants = array_values(array_unique([$year, (string) $year]));

        // Only admin-approved targets count as assigned
        return AssignedTarget::where('user_id', $staffId)
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->where('status', 'approved')
            ->get();
    }

    /**
     * Staff assigned target for period (current month records).
     * Weekly = monthly assigned * current week % of branch target (fallback 25%).
     */
    private function resolveStaffPeriodTarget(float $monthlyAssigned, Branch $branch, string $period): float
    {
        if ($monthlyAssigned <= 0) {
            return 0;
        }

        if ($period !== 'weekly') {
            return $monthlyAssigned;
        }

        $weekColumn = 'week_' . $this->currentWeekOfMonth();
        $month = $this->currentMonthName();
        $year = $this->currentYearValue();

        $weekPercent = (float) (Target::where('branch_id', $branch->id)
            ->where(function ($q) use ($month) {
                $q->where('month', $month)
                    ->orWhere('month', strtolower($month))
                    ->orWhere('month', ucfirst(strtolower($month)));
            })
            ->where(function ($q) use ($year) {
                $q->where('year', $year)->orWhere('year', (string) $year);
            })
            ->avg($weekColumn) ?? 25);

        if ($weekPercent <= 0) {
            $weekPercent = 25;
        }

        return round(($monthlyAssigned * $weekPercent) / 100, 2);
    }

    private function asmComparisons(AreaSaleManager $asm, Carbon $from, Carbon $to, string $period): array
    {
        $branches = Branch::where('region_id', $asm->region_id)->get();

        return [
            'type' => 'asm',
            'branch_conversion' => $this->branchConversionRows($branches, $from, $to),
            'branch_category' => $this->branchCategoryRows($branches, $from, $to, $period),
            'region_conversion' => $this->regionConversionRows($asm->region_id, $from, $to),
            'staff_comparison' => $this->staffComparisonByBranches($branches, $from, $to, $period),
        ];
    }

    private function branchManagerComparisons(BranchManager $manager, Carbon $from, Carbon $to, string $branchCategoryFilter, string $period): array
    {
        $branch = $manager->branch;

        if (!$branch) {
            return [
                'type' => 'branch_manager',
                'summary' => null,
                'staff_comparison' => [],
                'branch_category' => [],
                'branch_category_filter' => $branchCategoryFilter,
                'branch_category_filters' => $this->branchCategoryFilters(),
                'branch_conversion' => [],
            ];
        }

        $peerBranches = Branch::where('region_id', $branch->region_id)->get();
        $month = $this->currentMonthName();
        $year = $this->currentYearValue();

        $monthlyTarget = (float) Target::where('branch_id', $branch->id)
            ->where(function ($q) use ($month) {
                $q->where('month', $month)
                    ->orWhere('month', strtolower($month))
                    ->orWhere('month', ucfirst(strtolower($month)));
            })
            ->where(function ($q) use ($year) {
                $q->where('year', $year)->orWhere('year', (string) $year);
            })
            ->sum('monthly_target');

        $periodTarget = $monthlyTarget;
        if ($period === 'weekly' && $monthlyTarget > 0) {
            $weekColumn = 'week_' . $this->currentWeekOfMonth();
            $weekPercent = (float) (Target::where('branch_id', $branch->id)
                ->where(function ($q) use ($month) {
                    $q->where('month', $month)
                        ->orWhere('month', strtolower($month))
                        ->orWhere('month', ucfirst(strtolower($month)));
                })
                ->where(function ($q) use ($year) {
                    $q->where('year', $year)->orWhere('year', (string) $year);
                })
                ->avg($weekColumn) ?? 25);
            if ($weekPercent <= 0) {
                $weekPercent = 25;
            }
            $periodTarget = round(($monthlyTarget * $weekPercent) / 100, 2);
        }

        $isAssigned = $periodTarget > 0;
        $achieved = $isAssigned
            ? min(
                $periodTarget,
                (float) Sale::where('shop_name', $branch->name)
                    ->whereBetween('date', [$from, $to])
                    ->sum('net_total')
            )
            : null;

        $achievedPercentage = ($isAssigned && $periodTarget > 0)
            ? min(100, round(($achieved / $periodTarget) * 100, 2))
            : null;

        return [
            'type' => 'branch_manager',
            'summary' => [
                'branch' => $branch->name,
                'is_assigned' => $isAssigned,
                'monthly_target' => $isAssigned ? $periodTarget : null,
                'achieved' => $achieved,
                'remaining' => $isAssigned ? max($periodTarget - $achieved, 0) : null,
                'achieved_percentage' => $achievedPercentage,
                'remaining_percentage' => $isAssigned ? max(0, 100 - min(100, $achievedPercentage)) : null,
                'commission' => $isAssigned ? round($achieved * 0.05, 2) : 0,
            ],
            'staff_comparison' => $this->staffComparisonForBranch($branch, $from, $to, $period),
            'branch_category' => $this->peerBranchCategoryRows($peerBranches, $branch->id, $branchCategoryFilter, $from, $to, $period),
            'branch_category_filter' => $branchCategoryFilter,
            'branch_category_filters' => $this->branchCategoryFilters(),
            'branch_conversion' => $this->branchConversionRows($peerBranches, $from, $to),
        ];
    }

    private function saleStaffComparisons(SaleStaff $staff, Carbon $from, Carbon $to, string $period): array
    {
        $targets = $this->getStaffMonthTargets($staff->id);
        $assigned = ['garments' => 0, 'unstitched' => 0, 'accessories' => 0];

        foreach ($targets as $target) {
            $key = strtolower(trim((string) $target->category));
            if (isset($assigned[$key])) {
                $assigned[$key] += max(0, (float) $target->target);
            }
        }

        if ($staff->branch) {
            foreach ($assigned as $category => $value) {
                $assigned[$category] = $this->resolveStaffPeriodTarget($value, $staff->branch, $period);
            }
        } elseif ($period === 'weekly') {
            foreach ($assigned as $category => $value) {
                $assigned[$category] = round($value * 0.25, 2);
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
        $cappedTotalQty = 0;
        foreach ($assigned as $category => $target) {
            $isAssigned = $target > 0;
            $achieved = $isAssigned ? min($target, $sold[$category]) : null;
            if ($isAssigned) {
                $cappedTotalQty += $achieved;
            }
            $percentage = $isAssigned ? min(100, round(($achieved / $target) * 100)) : null;
            $targetVsAchievement[] = [
                'category' => ucfirst($category),
                'is_assigned' => $isAssigned,
                'target' => $isAssigned ? $target : null,
                'achieved' => $achieved,
                'achieved_percentage' => $percentage,
                'remaining_percentage' => $isAssigned ? (100 - $percentage) : null,
            ];
        }

        $totalTarget = array_sum($assigned);
        $isOverallAssigned = $totalTarget > 0;
        $totalQty = $isOverallAssigned ? min($totalTarget, $cappedTotalQty) : $totalQty;
        $overallPercentage = $isOverallAssigned
            ? min(100, round(($totalQty / $totalTarget) * 100))
            : null;

        $conversionChart = [];
        $peak = null;

        if ($staff->branch) {
            $cursor = $from->copy()->startOfDay();
            $end = $to->copy()->startOfDay();
            $today = Carbon::today();
            if ($end->gt($today)) {
                $end = $today->copy();
            }

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
                'is_assigned' => $isOverallAssigned,
                'target' => $isOverallAssigned ? $totalTarget : null,
                'achieved' => $isOverallAssigned ? $totalQty : null,
                'achieved_percentage' => $overallPercentage,
                'remaining_percentage' => $isOverallAssigned ? (100 - $overallPercentage) : null,
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

    private function branchCategoryRows($branches, Carbon $from, Carbon $to, string $period): array
    {
        $response = [];

        foreach ($this->categories as $category) {
            $rows = [];

            foreach ($branches as $branch) {
                $targetRecord = $this->findBranchTarget((int) $branch->id, $category);
                $target = $this->resolveBranchCategoryTarget($targetRecord, $period);
                $isAssigned = $target > 0;
                $achieved = $isAssigned
                    ? min($target, $this->achievedQtyForBranch($branch->name, $category, $from, $to))
                    : null;
                $percentage = $isAssigned ? min(100, round(($achieved / $target) * 100)) : null;

                $rows[] = [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'is_assigned' => $isAssigned,
                    'target' => $isAssigned ? $target : null,
                    'achieved' => $achieved,
                    'achievement_percentage' => $percentage ?? -1,
                    'remaining_percentage' => $isAssigned ? (100 - $percentage) : null,
                ];
            }

            $response[] = [
                'category' => ucfirst($category),
                'branches' => $this->rankBy($rows, 'achievement_percentage'),
            ];
        }

        return $response;
    }

    private function peerBranchCategoryRows($branches, int $yourBranchId, string $selectedCategory, Carbon $from, Carbon $to, string $period): array
    {
        $rows = [];

        foreach ($branches as $branchItem) {
            if ($selectedCategory === 'overall') {
                $target = 0;
                $achievedRaw = 0;
                foreach ($this->categories as $category) {
                    $targetRecord = $this->findBranchTarget((int) $branchItem->id, $category);
                    $target += $this->resolveBranchCategoryTarget($targetRecord, $period);
                    $achievedRaw += $this->achievedQtyForBranch($branchItem->name, $category, $from, $to);
                }
            } else {
                $targetRecord = $this->findBranchTarget((int) $branchItem->id, $selectedCategory);
                $target = $this->resolveBranchCategoryTarget($targetRecord, $period);
                $achievedRaw = $this->achievedQtyForBranch($branchItem->name, $selectedCategory, $from, $to);
            }

            $isAssigned = $target > 0;
            $achieved = $isAssigned ? min($target, $achievedRaw) : null;
            $achievement = $isAssigned ? min(100, round(($achieved / $target) * 100)) : null;

            $rows[] = [
                'branch_id' => $branchItem->id,
                'branch' => $branchItem->name,
                'is_assigned' => $isAssigned,
                'target' => $isAssigned ? $target : null,
                'achieved' => $achieved,
                'achievement' => $achievement ?? -1,
                'remaining' => $isAssigned ? (100 - $achievement) : null,
            ];
        }

        $ranked = $this->rankBy($rows, 'achievement');
        $yourBranch = collect($ranked)->firstWhere('branch_id', $yourBranchId);
        $otherBranches = collect($ranked)->where('branch_id', '!=', $yourBranchId)->values()->all();

        return [
            'category' => $selectedCategory === 'overall' ? 'Overall' : ucfirst($selectedCategory),
            'your_branch' => $yourBranch,
            'branches' => $otherBranches,
        ];
    }

    private function branchCategoryFilters(): array
    {
        return [
            'overall' => 'Overall',
            'garments' => 'Garments',
            'unstitched' => 'Unstitched',
            'accessories' => 'Accessories',
        ];
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

    private function staffComparisonByBranches($branches, Carbon $from, Carbon $to, string $period): array
    {
        $response = [];

        foreach ($branches as $branch) {
            $response[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'staff' => $this->staffComparisonForBranch($branch, $from, $to, $period),
            ];
        }

        return $response;
    }

    private function staffComparisonForBranch(Branch $branch, Carbon $from, Carbon $to, string $period): array
    {
        $commissionRate = Commission::where('role', 'sales_staff')->value('commission') ?? 0;
        $staffList = SaleStaff::where('branch_id', $branch->id)->get();
        $staffData = [];

        foreach ($staffList as $staff) {
            $monthlyAssigned = 0;
            foreach ($this->getStaffMonthTargets($staff->id) as $assignedTarget) {
                $monthlyAssigned += max(0, (float) $assignedTarget->target);
            }

            $target = $this->resolveStaffPeriodTarget($monthlyAssigned, $branch, $period);
            $isAssigned = $target > 0;

            $saleItems = SaleItem::where('salesperson_code', $staff->employee_id)
                ->whereHas('sale', function ($q) use ($from, $to, $branch) {
                    $q->where('shop_name', $branch->name)
                        ->whereBetween('date', [$from, $to]);
                })
                ->get(['quantity', 'price']);

            $achievedRaw = (float) $saleItems->sum(function ($item) {
                return max(0, (float) $item->quantity);
            });

            $saleAmount = (float) $saleItems->sum(function ($item) {
                return max(0, (float) $item->quantity) * max(0, (float) $item->price);
            });

            $achieved = $isAssigned ? min($target, $achievedRaw) : null;
            $percentage = $isAssigned ? min(100, round(($achieved / $target) * 100)) : null;

            $staffData[] = [
                'staff_id' => $staff->id,
                'name' => $staff->name,
                'is_assigned' => $isAssigned,
                'target' => $isAssigned ? $target : null,
                'achieved' => $achieved,
                'remaining' => $isAssigned ? max($target - $achieved, 0) : null,
                'achievement_percentage' => $percentage ?? -1,
                'remaining_percentage' => $isAssigned ? (100 - $percentage) : null,
                'commission' => $isAssigned ? round(($saleAmount * $commissionRate) / 100, 2) : 0,
            ];
        }

        return $this->rankBy($staffData, 'achievement_percentage');
    }

    private function achievedQtyForBranch(string $branchName, string $category, Carbon $from, Carbon $to): int
    {
        $achieved = 0;
        $items = SaleItem::whereHas('sale', function ($q) use ($branchName, $from, $to) {
            $q->where('shop_name', $branchName)
                ->whereBetween('date', [$from, $to]);
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
