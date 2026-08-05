<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignedTarget;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\SaleItem;
use App\Models\SaleStaff;
use App\Models\Target;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchManagerComparisonController extends Controller
{
    public function staffComparison(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $branch = $user->branch;

        if (!$branch) {
            return response()->json([
                'status' => 404,
                'message' => 'Branch not found'
            ], 404);
        }

        $type = strtolower($request->type ?? 'monthly');
        [$from, $to, $type] = $this->resolvePeriod($type);

        $now = Carbon::now();
        $month = $now->format('F');
        $year = (string) $now->year;

        $commissionRate = (float) (Commission::where('role', 'sales_staff')
            ->value('commission') ?? 0);

        $categoryMappings = $this->categoryMappings();
        $staffs = SaleStaff::where('branch_id', $branch->id)->get();
        $response = [];

        foreach ($staffs as $staff) {
            $assignedTargets = [
                'garments' => 0,
                'unstitched' => 0,
                'accessories' => 0,
            ];

            $targets = $this->getStaffMonthTargets($staff->id, $month, $year);
            $hasApprovedAssignment = $this->hasApprovedAssignment($staff->id, $month, $year);

            foreach ($targets as $target) {
                $category = strtolower(trim((string) $target->category));

                if (array_key_exists($category, $assignedTargets)) {
                    $assignedTargets[$category] += max(0, (float) $target->target);
                }
            }

            $achieved = [
                'garments' => 0,
                'unstitched' => 0,
                'accessories' => 0,
            ];

            $saleItems = SaleItem::where('salesperson_code', (string) $staff->employee_id)
                ->whereHas('sale', function ($q) use ($branch, $from, $to) {
                    $q->where('shop_name', $branch->name)
                        ->whereBetween('date', [
                            $from->toDateString(),
                            $to->toDateString(),
                        ]);
                })
                ->get(['category', 'quantity', 'price']);

            $saleAmount = 0;

            foreach ($saleItems as $item) {
                $qty = max(0, (float) $item->quantity);
                $itemCategory = strtolower(trim((string) $item->category));

                foreach ($categoryMappings as $category => $mapping) {
                    if (in_array($itemCategory, $mapping, true)) {
                        $achieved[$category] += $qty;

                        if ($assignedTargets[$category] > 0) {
                            $saleAmount += $qty * max(0, (float) $item->price);
                        }
                        break;
                    }
                }
            }

            foreach ($achieved as $category => $value) {
                $achieved[$category] = min($value, $assignedTargets[$category]);
            }

            $totalTarget = array_sum($assignedTargets);
            $totalAchieved = array_sum($achieved);
            $remaining = max($totalTarget - $totalAchieved, 0);

            $percentage = $totalTarget > 0
                ? min(100, (int) round(($totalAchieved / $totalTarget) * 100))
                : 0;

            $remainingPercentage = $totalTarget > 0 ? (100 - $percentage) : 0;

            $commission = $totalTarget > 0
                ? round($saleAmount * ($commissionRate / 100), 2)
                : 0;

            $response[] = [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'assigned' => $hasApprovedAssignment,
                'target' => $totalTarget,
                'achieved' => $totalAchieved,
                'remaining' => $remaining,
                'achieved_percentage' => $percentage,
                'remaining_percentage' => $remainingPercentage,
                'commission' => $commission,
            ];
        }

        usort($response, function ($a, $b) {
            return $b['achieved_percentage'] <=> $a['achieved_percentage'];
        });

        foreach ($response as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);

        return response()->json([
            'status' => 200,
            'message' => 'Staff comparison',
            'type' => $type,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'data' => $response,
        ]);
    }

public function branchComparison(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => 401,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $branch = $user->branch;

    if (!$branch) {
        return response()->json([
            'status' => 404,
            'message' => 'Branch not found'
        ], 404);
    }

    $type = strtolower($request->type ?? 'monthly');
    [$from, $to, $type] = $this->resolvePeriod($type);

    $regionId = $branch->region_id;

    $month = Carbon::now()->format('F');
    $year = Carbon::now()->year;
    $weekNumber = $this->currentWeekOfMonth();
    $weekColumn = 'week_' . $weekNumber;

    $categories = [
        'garments',
        'unstitched',
        'accessories'
    ];

    $categoryMappings = $this->categoryMappings();
    $branches = Branch::where('region_id', $regionId)->get();

    $response = [];

    foreach ($categories as $category) {

        $rows = [];

        foreach ($branches as $branchItem) {

            $targetRecord = Target::where('branch_id', $branchItem->id)
                ->where('category', $category)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($type === 'weekly') {
                $target = $targetRecord ? (float) ($targetRecord->{$weekColumn} ?? 0) : 0;
            } else {
                $target = $targetRecord ? (float) ($targetRecord->monthly_target ?? 0) : 0;
            }

            $achieved = 0;

            $saleItems = SaleItem::whereHas('sale', function ($q) use ($branchItem, $from, $to) {
                $q->where('shop_name', $branchItem->name)
                    ->whereBetween('date', [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]);
            })->get(['category', 'quantity']);

            $mapping = $categoryMappings[$category] ?? [];

            foreach ($saleItems as $item) {
                $itemCategory = strtolower(trim((string) $item->category));
                $qty = max(0, (float) $item->quantity);

                if (in_array($itemCategory, $mapping, true)) {
                    $achieved += $qty;
                }
            }

            $achieved = $target > 0 ? min($achieved, $target) : 0;

            $achievement = $target > 0
                ? min(100, (int) round(($achieved / $target) * 100))
                : 0;

            $rows[] = [
                'branch_id' => $branchItem->id,
                'branch' => $branchItem->name,
                'target' => $target,
                'achieved' => $achieved,
                'achievement' => $achievement,
                'remaining' => 100 - $achievement,
            ];
        }

        usort($rows, function ($a, $b) {
            return $b['achievement'] <=> $a['achievement'];
        });

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);

        $yourBranch = collect($rows)->firstWhere('branch_id', $branch->id);

        $otherBranches = collect($rows)
            ->where('branch_id', '!=', $branch->id)
            ->values();

        $response[] = [
            'category' => ucfirst($category),
            'your_branch' => $yourBranch,
            'branches' => $otherBranches,
        ];
    }

    return response()->json([
        'status' => 200,
        'message' => 'Branch Comparison',
        'type' => $type,
        'from' => $from->toDateString(),
        'to' => $to->toDateString(),
        'week' => $type === 'weekly' ? $weekNumber : null,
        'data' => $response,
    ]);
}

    public function staffDetails($id)
    {
        $branchManager = Auth::user();

        if (!$branchManager) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $branch = $branchManager->branch;

        if (!$branch) {
            return response()->json([
                'status' => 404,
                'message' => 'Branch not found'
            ], 404);
        }

        $staff = SaleStaff::with('designation')
            ->where('id', $id)
            ->where('branch_id', $branch->id)
            ->first();

        if (!$staff) {
            return response()->json([
                'status' => 404,
                'message' => 'Staff not found'
            ], 404);
        }

        if (empty($staff->employee_id)) {
            return response()->json([
                'status' => 422,
                'message' => 'Staff employee ID is missing'
            ], 422);
        }

        $now = Carbon::now();
        $month = $now->format('F');
        $year = (string) $now->year;
        $monthNum = $now->month;

        $categoryMappings = $this->categoryMappings();

        $assigned = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

        $targets = $this->getStaffMonthTargets($staff->id, $month, $year);

        foreach ($targets as $target) {
            $category = strtolower(trim((string) $target->category));

            if (array_key_exists($category, $assigned)) {
                $assigned[$category] = max(0, (float) $target->target);
            }
        }

        $achieved = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

        $saleItems = SaleItem::where('salesperson_code', (string) $staff->employee_id)
            ->whereHas('sale', function ($q) use ($branch, $monthNum, $year) {
                $q->where('shop_name', $branch->name)
                    ->whereMonth('date', $monthNum)
                    ->whereYear('date', $year);
            })
            ->get(['category', 'quantity']);

        foreach ($saleItems as $item) {
            $itemCategory = strtolower(trim((string) $item->category));
            $qty = max(0, (float) $item->quantity);

            foreach ($categoryMappings as $category => $mapping) {
                if (in_array($itemCategory, $mapping, true)) {
                    $achieved[$category] += $qty;
                    break;
                }
            }
        }

        $performance = [];
        $totalTarget = 0;
        $totalAchieved = 0;

        foreach ($assigned as $category => $target) {
            $sold = $achieved[$category];
            $done = min($sold, $target);

            $percentage = $target > 0
                ? min(100, (int) round(($done / $target) * 100))
                : 0;

            $totalTarget += $target;
            $totalAchieved += $done;

            $performance[] = [
                'category' => ucfirst($category),
                'target' => $target,
                'achieved' => $done,
                'remaining' => max(0, $target - $done),
                'percentage' => $percentage,
            ];
        }

        $remaining = max(0, $totalTarget - $totalAchieved);
        $achievedPercentage = $totalTarget > 0
            ? min(100, (int) round(($totalAchieved / $totalTarget) * 100))
            : 0;

        return response()->json([
            'status' => 200,
            'message' => 'Staff details',
            'data' => [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'designation' => optional($staff->designation)->name,
                'branch' => $branch->name,
                'month' => $month,
                'year' => $year,
                'target' => $totalTarget,
                'achieved' => $totalAchieved,
                'remaining' => $remaining,
                'achieved_percentage' => $achievedPercentage,
                'remaining_percentage' => 100 - $achievedPercentage,
                'category_performance' => $performance,
            ]
        ]);
    }

    private function categoryMappings(): array
    {
        return [
            'garments' => [
                'signature',
                'flowy',
                'trouser',
                'regular prints',
                'fusion co-ords',
                'festive',
                'composed rotary',
                'premium',
                'casual',
                'glam',
                'dailywear',
                'regular running',
                'regular panel',
                'modish',
                'trendy',
                'premium wear',
                'tops',
            ],
            'unstitched' => [
                'dupatta - dyed',
                'unstitched trousers',
            ],
            'accessories' => [
                'hand bag',
                'scarves - printed',
                'sunglasses',
                'jewellery',
                'clutches',
                'perfumes',
                'body mist',
                'non-tradable',
            ],
        ];
    }

    private function resolvePeriod(string $type): array
    {
        if ($type === 'weekly') {
            return [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
                'weekly',
            ];
        }

        return [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
            'monthly',
        ];
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

    private function hasApprovedAssignment($staffId, string $month, string $year): bool
    {
        $monthVariants = array_values(array_unique([
            $month,
            strtolower($month),
            ucfirst(strtolower($month)),
            Carbon::now()->format('m'),
            (string) Carbon::now()->month,
        ]));

        $yearVariants = array_values(array_unique([
            $year,
            (int) $year,
        ]));

        return AssignedTarget::where('user_id', $staffId)
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->where('status', 'approved')
            ->where('target', '>', 0)
            ->exists();
    }

    private function getStaffMonthTargets($staffId, string $month, string $year)
    {
        $monthVariants = array_values(array_unique([
            $month,
            strtolower($month),
            ucfirst(strtolower($month)),
            Carbon::now()->format('m'),
            (string) Carbon::now()->month,
        ]));

        $yearVariants = array_values(array_unique([
            $year,
            (int) $year,
        ]));

        // Only admin-approved targets count as assigned
        return AssignedTarget::where('user_id', $staffId)
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->where('status', 'approved')
            ->get();
    }
}
