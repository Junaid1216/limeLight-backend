<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\AssignedTarget;
use App\Models\Branch;
use App\Models\Commission;
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
    |--------------------------------------------------------------------------
    */

    $type = $request->type ?? 'weekly';

    if ($type == 'monthly') {

        $from = Carbon::now()->startOfMonth();

        $to = Carbon::now()->endOfMonth();

    } else {

        $from = Carbon::today()->subDays(6)->startOfDay();

        $to = Carbon::today()->endOfDay();

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
            ->whereBetween('date', [$from, $to])
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

            'from' => $from->toDateString(),

            'to' => $to->toDateString(),

            'branches' => $rows

        ]

    ]);
}

public function branchComparison()
{
    $asm = Auth::user();

    if (!$asm) {
        return response()->json([
            'status' => 401,
            'message' => 'Unauthenticated'
        ], 401);
    }

    $month = Carbon::now()->format('F');
    $year = Carbon::now()->year;

    $categories = [
        'garments',
        'unstitched',
        'accessories'
    ];

    $response = [];

    $branches = Branch::where('region_id', $asm->region_id)->get();

    foreach ($categories as $category) {

        $rows = [];

        foreach ($branches as $branch) {

            $target = Target::where('branch_id', $branch->id)
                ->where('month', $month)
                ->where('year', $year)
                ->where('category', $category)
                ->value('monthly_target') ?? 0;

            $achieved = 0;

            $items = SaleItem::whereHas('sale', function ($q) use ($branch) {

                $q->where('shop_name', $branch->name);

            })->get();

            foreach ($items as $item) {

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

            $achieved = min($achieved, $target);

            $percentage = $target > 0
                ? round(($achieved / $target) * 100)
                : 0;

            if ($percentage > 100) {
                $percentage = 100;
            }

            $rows[] = [

                'branch_id' => $branch->id,

                'branch_name' => $branch->name,

                'target' => $target,

                'achieved' => $achieved,

                'achievement_percentage' => $percentage,

                'remaining_percentage' => 100 - $percentage

            ];

        }

        usort($rows, function ($a, $b) {

            return $b['achievement_percentage'] <=> $a['achievement_percentage'];

        });

        foreach ($rows as $index => &$row) {

            $row['rank'] = $index + 1;

        }

        $response[] = [

            'category' => ucfirst($category),

            'branches' => $rows

        ];

    }

    return response()->json([

        'status' => 200,

        'message' => 'Branch Comparison',

        'data' => $response

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

    $type = $request->type ?? 'weekly';

     if ($type == 'monthly') {

        $from = Carbon::now()->startOfMonth();

        $to = Carbon::now()->endOfMonth();

    } else {

        $from = Carbon::today()->subDays(6)->startOfDay();

        $to = Carbon::today()->endOfDay();

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

        'from' => $from->toDateString(),

        'to' => $to->toDateString(),

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

    $commissionRate = Commission::where('role', 'sales_staff')
        ->value('commission') ?? 0;

    $branches = Branch::where('region_id', $asm->region_id)->get();

    $categories = [
        'garments',
        'unstitched',
        'accessories'
    ];

    $garmentsCategories = [
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
    ];

    $unstitchedCategories = [
        'dupatta - dyed',
        'unstitched trousers'
    ];

    $accessoriesCategories = [
        'hand bag',
        'scarves - printed',
        'sunglasses',
        'jewellery',
        'clutches',
        'perfumes',
        'body mist',
        'non-tradable'
    ];

    $response = [];

    foreach ($branches as $branch) {

        $staffList = SaleStaff::where('branch_id', $branch->id)->get();

        $staffData = [];

        foreach ($staffList as $staff) {

            /*
            |--------------------------------------------------------------------------
            | Total Target
            |--------------------------------------------------------------------------
            */

            $target = AssignedTarget::where('user_id', $staff->id)
                ->sum('target');


            /*
            |--------------------------------------------------------------------------
            | Get Staff Sales
            |--------------------------------------------------------------------------
            */

            $saleItems = SaleItem::where(
                    'salesperson_code',
                    $staff->employee_id
                )
                ->whereHas('sale', function ($q) use ($branch) {
                    $q->where('shop_name', $branch->name);
                })
                ->get();


            /*
            |--------------------------------------------------------------------------
            | Category Achieved
            |--------------------------------------------------------------------------
            */

            $categoryAchieved = [
                'garments' => 0,
                'unstitched' => 0,
                'accessories' => 0
            ];


            foreach ($saleItems as $item) {

                $itemCategory = strtolower(trim($item->category));

                $qty = max(0, (float) $item->quantity);


                /*
                |--------------------------------------------------------------------------
                | Garments
                |--------------------------------------------------------------------------
                */

                if (in_array($itemCategory, $garmentsCategories)) {

                    $categoryAchieved['garments'] += $qty;

                }


                /*
                |--------------------------------------------------------------------------
                | Unstitched
                |--------------------------------------------------------------------------
                */

                elseif (in_array($itemCategory, $unstitchedCategories)) {

                    $categoryAchieved['unstitched'] += $qty;

                }


                /*
                |--------------------------------------------------------------------------
                | Accessories
                |--------------------------------------------------------------------------
                */

                elseif (in_array($itemCategory, $accessoriesCategories)) {

                    $categoryAchieved['accessories'] += $qty;

                }
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate Category Targets
            |--------------------------------------------------------------------------
            */

            $categoryTargets = [];

            foreach ($categories as $category) {

                $categoryTargets[$category] = AssignedTarget::where([
                    'user_id' => $staff->id,
                    'category' => $category
                ])->sum('target');

            }


            /*
            |--------------------------------------------------------------------------
            | Cap Achieved Per Category
            |--------------------------------------------------------------------------
            */

            foreach ($categories as $category) {

                $categoryAchieved[$category] = min(
                    $categoryAchieved[$category],
                    $categoryTargets[$category]
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Total Achieved
            |--------------------------------------------------------------------------
            */

            $achieved = array_sum($categoryAchieved);


            /*
            |--------------------------------------------------------------------------
            | Final Safety Check
            |--------------------------------------------------------------------------
            */

            $achieved = min($achieved, $target);


            /*
            |--------------------------------------------------------------------------
            | Remaining
            |--------------------------------------------------------------------------
            */

            $remaining = max(
                $target - $achieved,
                0
            );


            /*
            |--------------------------------------------------------------------------
            | Achievement Percentage
            |--------------------------------------------------------------------------
            */

            $percentage = $target > 0
                ? round(($achieved / $target) * 100)
                : 0;

            $percentage = min($percentage, 100);


            /*
            |--------------------------------------------------------------------------
            | Commission
            |--------------------------------------------------------------------------
            */

            $saleAmount = $saleItems->sum(function ($item) {

                return max(0, $item->price * $item->quantity);

            });

            $commission = round(
                $saleAmount * ($commissionRate / 100),
                2
            );


            /*
            |--------------------------------------------------------------------------
            | Staff Data
            |--------------------------------------------------------------------------
            */

            $staffData[] = [

                'staff_id' => $staff->id,

                'name' => $staff->name,

                'target' => $target,

                'achieved' => $achieved,

                'remaining' => $remaining,

                'achievement_percentage' => $percentage,

                'remaining_percentage' => 100 - $percentage,

                'commission' => $commission

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Ranking
        |--------------------------------------------------------------------------
        */

        usort($staffData, function ($a, $b) {

            return $b['achievement_percentage']
                <=> $a['achievement_percentage'];

        });


        foreach ($staffData as $index => &$staff) {

            $staff['rank'] = $index + 1;

        }

        unset($staff);


        $response[] = [

            'branch_id' => $branch->id,

            'branch_name' => $branch->name,

            'staff_members' => count($staffData),

            'staff' => $staffData

        ];
    }


    return response()->json([

        'status' => 200,

        'message' => 'Staff Comparison',

        'data' => $response

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

            $targets = AssignedTarget::where('user_id', $staff->id)->get();

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
    $year  = Carbon::now()->year;

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
        | Assigned Target
        |--------------------------------------------------------------------------
        */

        $target = AssignedTarget::where([
                'user_id' => $staff->id,
                // 'month' => $month,
                // 'year' => $year,
                'category' => $category,
                // 'status' => 'approved'
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
}
