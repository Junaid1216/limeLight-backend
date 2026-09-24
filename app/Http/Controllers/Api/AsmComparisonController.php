<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CommissionHelper;
use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\AssignedTarget;
use App\Models\Branch;
use App\Models\FootfallDailySummary;
use App\Models\Region;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleStaff;
use App\Models\Target;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsmComparisonController extends Controller
{
    public function branchConversion(Request $request)
{
    $asm = Auth::user();
    
    $asm->region_id;

    if (!$asm) {
        return response()->json([
            'status' => 404,
            'message' => 'ASM not found'
        ], 404);
    }

    /*
    |--------------------------------------------------------------------------
    | Date Range
    | weekly  = current week of current month (week1: 1–7, week2: 8–14, ...)
    | monthly = current calendar month
    |--------------------------------------------------------------------------
    */

    $type = strtolower($request->type ?? 'weekly');

    if ($type === 'monthly') {
        $from = Carbon::now()->startOfMonth()->startOfDay();
        $to = Carbon::now()->endOfMonth()->endOfDay();
    } else {
        $type = 'weekly';
        [$from, $to] = $this->currentWeekRangeOfMonth();
    }

    /*
    |--------------------------------------------------------------------------
    | Branches Under Region
    |--------------------------------------------------------------------------
    */

    $branches = Branch::where('region_id', $asm->region_id)->get();

    $rows = [];

    foreach ($branches as $branch) {

        /*
        |--------------------------------------------------------------------------
        | Footfall
        |--------------------------------------------------------------------------
        */

        $traffic = FootfallDailySummary::where('branch_id', $branch->id)
            ->whereBetween('date', [
                $from->toDateString(),
                $to->toDateString()
            ])
            ->sum('footfall');

        /*
        |--------------------------------------------------------------------------
        | Invoices
        |--------------------------------------------------------------------------
        */

        $invoices = Sale::where('shop_name', $branch->name)
            ->whereRaw('DATE(`date`) BETWEEN ? AND ?', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Conversion
        |--------------------------------------------------------------------------
        */

        $conversion = $traffic > 0
            ? round(($invoices / $traffic) * 100, 2)
            : 0;

        $rows[] = [

            'branch_id' => $branch->id,

            'branch' => $branch->name,

            'traffic' => $traffic,

            'invoices' => $invoices,

            'conversion_percentage' => $conversion

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Ranking
    |--------------------------------------------------------------------------
    */

    usort($rows, function ($a, $b) {

        return $b['conversion_percentage'] <=> $a['conversion_percentage'];

    });

    foreach ($rows as $index => &$row) {

        $row['rank'] = $index + 1;

    }

    return response()->json([

        'status' => 200,

        'message' => 'Branch Conversion Comparison',

        'data' => [

            'type' => $type,

            'from' => $from->toDateString(),

            'to' => $to->toDateString(),

            'week' => $type === 'weekly' ? $this->currentWeekOfMonth() : null,

            'branches' => $rows

        ]

    ]);
}

public function branchComparison(Request $request)
{
    $asm = Auth::user();

    if (!$asm) {
        return response()->json([
            'status' => 401,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $type = strtolower($request->type ?? 'monthly');
    [$from, $to, $type] = $this->resolvePeriod($type);

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
    $response = [];
    $branches = Branch::where('region_id', $asm->region_id)->get();

    foreach ($categories as $category) {

        $rows = [];

        foreach ($branches as $branch) {

            $targetRecord = Target::where('branch_id', $branch->id)
                ->where('month', $month)
                ->where('year', $year)
                ->where('category', $category)
                ->first();

            if ($type === 'weekly') {
                $target = $targetRecord ? (float) ($targetRecord->{$weekColumn} ?? 0) : 0;
            } else {
                $target = $targetRecord ? (float) ($targetRecord->monthly_target ?? 0) : 0;
            }

            $achieved = 0;

            $items = SaleItem::whereHas('sale', function ($q) use ($branch, $from, $to) {
                $q->where('shop_name', $branch->name)
                    ->whereBetween('date', [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]);
            })->get(['category', 'quantity']);

            $mapping = $categoryMappings[$category] ?? [];

            foreach ($items as $item) {
                $qty = max(0, (float) $item->quantity);
                $itemCategory = strtolower(trim((string) $item->category));

                if (in_array($itemCategory, $mapping, true)) {
                    $achieved += $qty;
                }
            }

            $achieved = $target > 0 ? min($achieved, $target) : 0;

            $percentage = $target > 0
                ? min(100, (int) round(($achieved / $target) * 100))
                : 0;

            $rows[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'target' => $target,
                'achieved' => $achieved,
                'achievement_percentage' => $percentage,
                'remaining_percentage' => 100 - $percentage,
            ];
        }

        usort($rows, function ($a, $b) {
            return $b['achievement_percentage'] <=> $a['achievement_percentage'];
        });

        foreach ($rows as $index => &$row) {
            $row['rank'] = $index + 1;
        }
        unset($row);

        $response[] = [
            'category' => ucfirst($category),
            'branches' => $rows,
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

public function regionComparison(Request $request)
{
    $asm = Auth::user();

    if (!$asm) {
        return response()->json([
            'status' => 401,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $type = strtolower($request->type ?? 'weekly');

    if ($type === 'monthly') {
        $from = Carbon::now()->startOfMonth()->startOfDay();
        $to = Carbon::now()->endOfMonth()->endOfDay();
    } else {
        $type = 'weekly';
        [$from, $to] = $this->currentWeekRangeOfMonth();
    }

    $regions = Region::all();

    $rows = [];

    foreach ($regions as $region) {

        $branchIds = Branch::where('region_id', $region->id)
            ->pluck('id');

        $branchNames = Branch::whereIn('id', $branchIds)
            ->pluck('name');

        $traffic = FootfallDailySummary::whereIn('branch_id', $branchIds)
            ->whereBetween('date', [
                $from->toDateString(),
                $to->toDateString()
            ])
            ->sum('footfall');

        $invoices = Sale::whereIn('shop_name', $branchNames)
            ->whereRaw('DATE(`date`) BETWEEN ? AND ?', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->count();

        $conversion = $traffic > 0
            ? round(($invoices / $traffic) * 100, 2)
            : 0;

        $rows[] = [

            'region_id' => $region->id,

            'region' => $region->name,

            'traffic' => $traffic,

            'invoices' => $invoices,

            'conversion_percentage' => $conversion

        ];
    }

   /*
|--------------------------------------------------------------------------
| Ranking
|--------------------------------------------------------------------------
*/

usort($rows, function ($a, $b) {

    return $b['conversion_percentage'] <=> $a['conversion_percentage'];

});

foreach ($rows as $index => &$row) {

    $row['rank'] = $index + 1;

}

unset($row);

/*
|--------------------------------------------------------------------------
| Logged-in ASM Region
|--------------------------------------------------------------------------
*/

$yourRegion = collect($rows)
    ->firstWhere('region_id', $asm->region_id);

/*
|--------------------------------------------------------------------------
| Top 7 Other Regions
|--------------------------------------------------------------------------
*/

$otherRegions = collect($rows)
    ->where('region_id', '!=', $asm->region_id)
    ->take(7)
    ->values();

return response()->json([

    'status' => 200,

    'message' => 'Region Comparison',

    'data' => [

        'type' => $type,

        'from' => $from->toDateString(),

        'to' => $to->toDateString(),

        'week' => $type === 'weekly' ? $this->currentWeekOfMonth() : null,

        'your_region' => $yourRegion,

        'regions' => $otherRegions

    ]

]);
}

public function regionCategoryComparison()
{
    $asm = Auth::user();

    if (!$asm) {
        return response()->json([
            'status' => 401,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $month = Carbon::now()->format('F');
    $year  = Carbon::now()->year;

    $categories = [
        'garments',
        'unstitched',
        'accessories'
    ];

    $regions = Region::all();

    $response = [];

    foreach ($categories as $category) {

        $rows = [];

        foreach ($regions as $region) {

            $branchIds = Branch::where('region_id', $region->id)->pluck('id');

            $branchNames = Branch::whereIn('id', $branchIds)->pluck('name');

            /*
            |--------------------------------------------------------------------------
            | Total Target
            |--------------------------------------------------------------------------
            */

            $target = Target::whereIn('branch_id', $branchIds)
                ->where('category', $category)
                ->where('month', $month)
                ->where('year', $year)
                ->sum('monthly_target');

            /*
            |--------------------------------------------------------------------------
            | Achieved
            |--------------------------------------------------------------------------
            */

            $achieved = 0;

            $saleItems = SaleItem::whereHas('sale', function ($q) use ($branchNames) {
                $q->whereIn('shop_name', $branchNames);
            })->get();

            foreach ($saleItems as $item) {

                $qty = max(0, $item->quantity);

                $itemCategory = strtolower(trim($item->category));

                if (
                    $category == 'garments' &&
                    in_array($itemCategory, [
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
                        'tops'
                    ])
                ) {
                    $achieved += $qty;
                }

                elseif (
                    $category == 'unstitched' &&
                    in_array($itemCategory, [
                        'dupatta - dyed',
                        'unstitched trousers'
                    ])
                ) {
                    $achieved += $qty;
                }

                elseif (
                    $category == 'accessories' &&
                    in_array($itemCategory, [
                        'hand bag',
                        'scarves - printed',
                        'sunglasses',
                        'jewellery',
                        'clutches',
                        'perfumes',
                        'body mist',
                        'non-tradable'
                    ])
                ) {
                    $achieved += $qty;
                }
            }

             /*
            |--------------------------------------------------------------------------
            | Cap Achieved at Target
            |--------------------------------------------------------------------------
            */

            if ($target > 0 && $achieved > $target) {
                $achieved = $target;
            }

            /*
            |--------------------------------------------------------------------------
            | Achievement Percentage
            |--------------------------------------------------------------------------
            */

            $percentage = $target > 0
                ? round(($achieved / $target) * 100)
                : 0;

            if ($percentage > 100) {
                $percentage = 100;
            }

            $rows[] = [

                'region_id' => $region->id,

                'region' => $region->name,

                'target' => $target,

                'achieved' => $achieved,

                'achievement_percentage' => $percentage,

                'remaining_percentage' => 100 - $percentage

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Ranking
        |--------------------------------------------------------------------------
        */

        usort($rows, function ($a, $b) {

            return $b['achievement_percentage']
                <=> $a['achievement_percentage'];

        });

        /*
        |--------------------------------------------------------------------------
        | Assign Actual Rank
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $index => &$row) {

            $row['rank'] = $index + 1;

        }

        unset($row);

        /*
        |--------------------------------------------------------------------------
        | Logged-in ASM Region
        |--------------------------------------------------------------------------
        */

        $yourRegion = collect($rows)
            ->firstWhere('region_id', $asm->region_id);

        /*
        |--------------------------------------------------------------------------
        | Get Top 7 Regions
        |--------------------------------------------------------------------------
        */

        $topSeven = collect($rows)
            ->take(7)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Ensure Logged-in ASM Region Is Included
        |--------------------------------------------------------------------------
        */

        $yourRegionExists = $topSeven->contains(function ($region) use ($asm) {

            return $region['region_id'] == $asm->region_id;

        });

        if (!$yourRegionExists && $yourRegion) {

            // Remove the 7th region
            $topSeven = $topSeven
                ->take(6)
                ->values();

            // Add logged-in ASM's region
            $topSeven->push($yourRegion);

        }

        /*
        |--------------------------------------------------------------------------
        | Sort Again By Actual Rank
        |--------------------------------------------------------------------------
        */

        $topSeven = $topSeven
            ->sortBy('rank')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        $response[] = [

            'category' => ucfirst($category),

            'your_region' => $yourRegion,

            'regions' => $topSeven

        ];
    }

    return response()->json([

        'status' => 200,

        'message' => 'Region Category Comparison',

        'data' => $response

    ]);
}

public function staffComparison(Request $request)
{
    $asm = Auth::user();

    if (!$asm) {
        return response()->json([
            'status' => 401,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $now = Carbon::now();
    $month = $now->format('F');
    $year = (string) $now->year;

    $monthVariants = array_values(array_unique([
        $month,
        strtolower($month),
        ucfirst(strtolower($month)),
        $now->format('m'),
        (string) $now->month,
    ]));

    $yearVariants = array_values(array_unique([
        $year,
        (int) $year,
    ]));

    $from = $now->copy()->startOfMonth()->startOfDay();
    $to = $now->copy()->endOfMonth()->endOfDay();

    $categoryMappings = [
        'garments' => [
            'signature', 'flowy', 'trouser', 'regular prints', 'fusion co-ords', 'festive',
            'composed rotary', 'premium', 'casual', 'glam', 'dailywear', 'regular running',
            'regular panel', 'modish', 'trendy', 'premium wear', 'tops',
        ],
        'unstitched' => [
            'dupatta - dyed', 'unstitched trousers',
        ],
        'accessories' => [
            'hand bag', 'scarves - printed', 'sunglasses', 'jewellery', 'clutches',
            'perfumes', 'body mist', 'non-tradable',
        ],
    ];

    $branches = Branch::where('region_id', $asm->region_id)->get();
    $response = [];
    $rateCache = [];

    foreach ($branches as $branch) {
        $staffList = SaleStaff::where('branch_id', $branch->id)
            ->get(['id', 'name', 'employee_id', 'branch_id']);

        $employeeIds = $staffList->pluck('employee_id')
            ->filter()
            ->map(function ($id) {
                return (string) $id;
            })
            ->values()
            ->all();

        $itemsByStaff = collect();
        if (!empty($employeeIds)) {
            $items = SaleItem::query()
                ->select([
                    'sale_items.invoice_id',
                    'sale_items.salesperson_code',
                    'sale_items.category',
                    'sale_items.quantity',
                    'sale_items.discount',
                    'sale_items.price',
                    'sales.date',
                ])
                ->join('sales', 'sales.invoice_id', '=', 'sale_items.invoice_id')
                ->where('sales.shop_name', $branch->name)
                ->whereBetween('sales.date', [$from, $to])
                ->whereIn('sale_items.salesperson_code', $employeeIds)
                ->get();

            $itemsByStaff = $items->groupBy(function ($item) {
                return (string) $item->salesperson_code;
            });
        }

        $staffData = [];

        foreach ($staffList as $staff) {
            /*
            |--------------------------------------------------------------------------
            | Target / Achieved — same as admin sale staff + BM staff comparison
            | garments / unstitched / accessories; cap per category then sum
            |--------------------------------------------------------------------------
            */

            $assignedTargets = AssignedTarget::where('user_id', $staff->id)
                ->whereIn('month', $monthVariants)
                ->whereIn('year', $yearVariants)
                ->where('status', 'approved')
                ->get();

            $assigned = [
                'garments' => 0.0,
                'unstitched' => 0.0,
                'accessories' => 0.0,
            ];

            foreach ($assignedTargets as $assignedTarget) {
                $key = strtolower(trim((string) $assignedTarget->category));
                if (isset($assigned[$key])) {
                    $assigned[$key] += max(0, (float) $assignedTarget->target);
                }
            }

            $target = array_sum($assigned);
            $isAssigned = $target > 0;

            $saleItems = $itemsByStaff->get((string) $staff->employee_id, collect());

            $sold = [
                'garments' => 0.0,
                'unstitched' => 0.0,
                'accessories' => 0.0,
            ];

            foreach ($saleItems as $item) {
                $itemCategory = strtolower(trim((string) ($item->category ?? '')));
                foreach ($categoryMappings as $category => $mapping) {
                    if (in_array($itemCategory, $mapping, true)) {
                        $sold[$category] += max(0, (float) $item->quantity);
                        break;
                    }
                }
            }

            $cappedTotal = 0.0;
            foreach ($assigned as $category => $catTarget) {
                if ($catTarget > 0) {
                    $cappedTotal += min($catTarget, $sold[$category]);
                }
            }

            $achieved = $isAssigned ? min($target, $cappedTotal) : 0;
            $remaining = $isAssigned ? max($target - $achieved, 0) : 0;

            $percentage = $isAssigned
                ? (int) min(100, round(($achieved / $target) * 100))
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Commission — Sales History sale staff formula
            | (price − discount) × qty × sales_staff rate @ sale date
            | Round per invoice, then sum
            |--------------------------------------------------------------------------
            */

            $commission = 0.0;
            if ($isAssigned) {
                foreach ($saleItems->groupBy('invoice_id') as $invoiceItems) {
                    $saleDate = $invoiceItems->first()->date;
                    $dateKey = (string) $saleDate;
                    if (!array_key_exists($dateKey, $rateCache)) {
                        $rateCache[$dateKey] = CommissionHelper::rateFor('sales_staff', $saleDate);
                    }
                    $rate = $rateCache[$dateKey];

                    $invoiceCommission = round($invoiceItems->sum(function ($item) use ($rate) {
                        $price = max(0, (float) $item->price);
                        $discount = max(0, (float) $item->discount);
                        $quantity = (int) $item->quantity;
                        $salesAmount = ($price - $discount) * $quantity;

                        return ($salesAmount * $rate) / 100;
                    }), 2);

                    $commission += $invoiceCommission;
                }
                $commission = round($commission, 2);
            }

            $staffData[] = [
                'staff_id' => $staff->id,
                'name' => $staff->name,
                'is_assigned' => $isAssigned,
                'target' => $isAssigned ? $target : 0,
                'achieved' => $isAssigned ? $achieved : 0,
                'remaining' => $remaining,
                'achievement_percentage' => $percentage,
                'remaining_percentage' => $isAssigned ? (100 - $percentage) : 0,
                'commission' => $commission,
            ];
        }

        usort($staffData, function ($a, $b) {
            return $b['achievement_percentage'] <=> $a['achievement_percentage'];
        });

        foreach ($staffData as $index => &$staffRow) {
            $staffRow['rank'] = $index + 1;
        }
        unset($staffRow);

        $response[] = [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'staff_members' => count($staffData),
            'staff' => $staffData,
        ];
    }

    return response()->json([
        'status' => 200,
        'message' => 'Staff Comparison',
        'data' => $response,
    ]);
}

public function branchTargets()
{
    $asm = Auth::user();

    if (!$asm) {
        return response()->json([
            'status' => 401,
            'message' => 'Unauthenticated'
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Branches of ASM Region
    |--------------------------------------------------------------------------
    */

    $branches = Branch::where('region_id', $asm->region_id)->get();

    $response = [];

    foreach ($branches as $branch) {

        $staffMembers = SaleStaff::where('branch_id', $branch->id)->get();

        $staffData = [];

        $totalGarments = 0;
        $totalUnstitched = 0;
        $totalAccessories = 0;

        foreach ($staffMembers as $staff) {

            $month = Carbon::now()->format('F');
            $year = (string) Carbon::now()->year;

            $targets = AssignedTarget::where('user_id', $staff->id)
                ->where('month', $month)
                ->where('year', $year)
                ->where('status', 'approved')
                ->get();

            $garments = 0;
            $unstitched = 0;
            $accessories = 0;

            foreach ($targets as $target) {

                switch (strtolower($target->category)) {

                    case 'garments':
                        $garments += $target->target;
                        break;

                    case 'unstitched':
                        $unstitched += $target->target;
                        break;

                    case 'accessories':
                        $accessories += $target->target;
                        break;
                }
            }

            $totalGarments += $garments;
            $totalUnstitched += $unstitched;
            $totalAccessories += $accessories;

            $staffData[] = [

                'staff_id' => $staff->id,
                'name' => $staff->name,
                'garments' => $garments,
                'unstitched' => $unstitched,
                'accessories' => $accessories

            ];
        }

        $response[] = [

            'branch_id' => $branch->id,

            'branch_name' => $branch->name,

            'staff_members' => $staffMembers->count(),

            'staff' => $staffData,

            'total' => [

                'garments' => $totalGarments,

                'unstitched' => $totalUnstitched,

                'accessories' => $totalAccessories

            ]

        ];
    }

    return response()->json([

        'status' => 200,

        'message' => 'Branch Targets',

        'data' => $response

    ]);
}

public function staffDetails($staffId)
{
    $asm = Auth::user();

    $branchIds = Branch::where('region_id', $asm->region_id)
        ->pluck('id');

    $staff = SaleStaff::whereIn('branch_id', $branchIds)
        ->where('id', $staffId)
        ->first();

    if (!$staff) {

        return response()->json([
            'status' => 404,
            'message' => 'Staff not found'
        ],404);

    }

    $month = Carbon::now()->format('F');
    $year  = (string) Carbon::now()->year;

    $categories = [
        'garments',
        'unstitched',
        'accessories'
    ];

    $categoryPerformance = [];

    $totalTarget = 0;
    $totalAchieved = 0;

    foreach ($categories as $category) {

        /*
        |--------------------------------------------------------------------------
        | Assigned Target (only after admin approval)
        |--------------------------------------------------------------------------
        */

        $target = AssignedTarget::where([
                'user_id' => $staff->id,
                'month' => $month,
                'year' => $year,
                'category' => $category,
                'status' => 'approved',
            ])->sum('target');

        /*
        |--------------------------------------------------------------------------
        | Achieved
        |--------------------------------------------------------------------------
        */

        $achieved = 0;

       $saleItems = SaleItem::where('salesperson_code', $staff->employee_id)
    ->whereHas('sale', function ($q) use ($staff) {
        $q->where('shop_name', $staff->branch->name);
    })
    ->get();

        foreach ($saleItems as $item) {

            $itemCategory = strtolower(trim($item->category));

            $qty = max(0,$item->quantity);

            if (
                $category == 'garments' &&
                in_array($itemCategory,[
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
                    'tops'
                ])
            ) {

                $achieved += $qty;

            }

            elseif(
                $category == 'unstitched' &&
                in_array($itemCategory,[
                    'dupatta - dyed',
                    'unstitched trousers'
                ])
            ){

                $achieved += $qty;

            }

            elseif(
                $category == 'accessories' &&
                in_array($itemCategory,[
                    'hand bag',
                    'scarves - printed',
                    'sunglasses',
                    'jewellery',
                    'clutches',
                    'perfumes',
                    'body mist',
                    'non-tradable'
                ])
            ){

                $achieved += $qty;

            }

        }
        
        // Achieved should never be greater than target
        $achieved = min($achieved, $target);

        $remaining = max($target - $achieved, 0);

        $percentage = $target > 0
            ? round(($achieved / $target) * 100)
            : 0;

        $percentage = min($percentage, 100);


        if($percentage > 100){
            $percentage = 100;
        }

        $categoryPerformance[] = [

            'category' => ucfirst($category),

            'target' => $target,

            'achieved' => $achieved,

            'remaining' => $remaining,

            'percentage' => $percentage

        ];

        $totalTarget += $target;
        $totalAchieved += $achieved;

    }

    return response()->json([

        'status' => 200,

        'message' => 'Staff Details',

        'data' => [

            'sale_staff_id' => $staff->id,

            'name' => $staff->name,

            'designation' => optional($staff->designation)->name,

            'branch' => $staff->branch->name,

            'target' => $totalTarget,

            'achieved' => $totalAchieved,

            'remaining' => max($totalTarget-$totalAchieved,0),

            'categories' => $categoryPerformance

        ]

    ]);

}

    private function resolvePeriod(string $type): array
    {
        if ($type === 'weekly') {
            [$from, $to] = $this->currentWeekRangeOfMonth();

            return [$from, $to, 'weekly'];
        }

        return [
            Carbon::now()->startOfMonth()->startOfDay(),
            Carbon::now()->endOfMonth()->endOfDay(),
            'monthly',
        ];
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
}
