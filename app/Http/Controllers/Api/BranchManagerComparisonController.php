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
    public function staffComparison()
{
    $user = Auth::user();

    $branch = $user->branch;

    if (!$branch) {

        return response()->json([
            'status' => 404,
            'message' => 'Branch not found'
        ],404);
    }

    $month = Carbon::now()->format('F');
    $year = Carbon::now()->year;

    $commissionRate = Commission::where('role','sales_staff')
                        ->value('commission') ?? 0;

    $staffs = SaleStaff::where('branch_id',$branch->id)
                ->get();

    $response = [];

    foreach($staffs as $staff){

        /*
        |--------------------------------------------------------------------------
        | Target
        |--------------------------------------------------------------------------
        */

        $target = AssignedTarget::where('user_id',$staff->id)
                    ->sum('target');

        /*
        |--------------------------------------------------------------------------
        | Achieved
        |--------------------------------------------------------------------------
        */

        $achieved = SaleItem::where('salesperson_code',$staff->employee_id)
                    ->sum('quantity');

        /*
        |--------------------------------------------------------------------------
        | Sale Amount
        |--------------------------------------------------------------------------
        */

        $saleAmount = SaleItem::where('salesperson_code',$staff->employee_id)
                        ->selectRaw('SUM(quantity * price) as total')
                        ->value('total');

        $saleAmount = $saleAmount ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Commission
        |--------------------------------------------------------------------------
        */

        $commission = ($saleAmount * $commissionRate)/100;

        /*
        |--------------------------------------------------------------------------
        | Percentage
        |--------------------------------------------------------------------------
        */

        $percentage = $target > 0
            ? round(($achieved/$target)*100)
            :0;

        if($percentage>100){
            $percentage = 100;
        }

        $response[] = [

            'staff_id'=>$staff->id,

            'staff_name'=>$staff->name,

            'target'=>$target,

            'achieved_percentage'=>$percentage,

            'remaining_percentage'=>100-$percentage,

            'commission'=>round($commission,2)

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Ranking
    |--------------------------------------------------------------------------
    */

    usort($response,function($a,$b){

        return $b['achieved_percentage'] <=> $a['achieved_percentage'];

    });

    foreach($response as $index=>&$row){

        $row['rank']=$index+1;

    }

    return response()->json([

        'status'=>200,

        'message'=>'Staff comparison',

        'data'=>$response

    ]);
}

public function branchComparison()
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

    $regionId = $branch->region_id;

    $month = Carbon::now()->format('F');
    $year = Carbon::now()->year;

    $categories = [
        'garments',
        'unstitched',
        'accessories'
    ];

    $branches = Branch::where('region_id', $regionId)->get();

    $response = [];

    foreach ($categories as $category) {

        $rows = [];

        foreach ($branches as $branchItem) {

            /*
            |--------------------------------------------------------------------------
            | Monthly Target
            |--------------------------------------------------------------------------
            */

            $target = Target::where('branch_id', $branchItem->id)
                ->where('category', $category)
                ->where('month', $month)
                ->where('year', $year)
                ->value('monthly_target') ?? 0;

            /*
            |--------------------------------------------------------------------------
            | Achieved
            |--------------------------------------------------------------------------
            */

            $achieved = 0;

            $saleItems = SaleItem::whereHas('sale', function ($q) use ($branchItem) {
                $q->where('shop_name', $branchItem->name);
            })->get();

            foreach ($saleItems as $item) {

                $itemCategory = strtolower(trim($item->category));

                $qty = max(0, $item->quantity);

                /*
                |--------------------------------------------------------------------------
                | Garments
                |--------------------------------------------------------------------------
                */

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

                /*
                |--------------------------------------------------------------------------
                | Unstitched
                |--------------------------------------------------------------------------
                */

                elseif (
                    $category == 'unstitched' &&
                    in_array($itemCategory, [
                        'dupatta - dyed',
                        'unstitched trousers'
                    ])
                ) {

                    $achieved += $qty;

                }

                /*
                |--------------------------------------------------------------------------
                | Accessories
                |--------------------------------------------------------------------------
                */

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
            | Achievement %
            |--------------------------------------------------------------------------
            */

            $achievement = $target > 0
                ? round(($achieved / $target) * 100)
                : 0;

            if ($achievement > 100) {
                $achievement = 100;
            }

            $rows[] = [

                'branch_id' => $branchItem->id,

                'branch' => $branchItem->name,

                'achievement' => $achievement,

                'remaining' => 100 - $achievement

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Ranking
        |--------------------------------------------------------------------------
        */

        usort($rows, function ($a, $b) {

            return $b['achievement'] <=> $a['achievement'];

        });

        foreach ($rows as $index => &$row) {

            $row['rank'] = $index + 1;

        }

        /*
        |--------------------------------------------------------------------------
        | Current Branch
        |--------------------------------------------------------------------------
        */

        $yourBranch = collect($rows)->firstWhere('branch_id', $branch->id);

        /*
        |--------------------------------------------------------------------------
        | Other Branches
        |--------------------------------------------------------------------------
        */

        $otherBranches = collect($rows)
            ->where('branch_id', '!=', $branch->id)
            ->values();

        $response[] = [

            'category' => ucfirst($category),

            'your_branch' => $yourBranch,

            'branches' => $otherBranches

        ];
    }

    return response()->json([

        'status' => 200,

        'message' => 'Branch Comparison',

        'data' => $response

    ]);
}

public function staffDetails($id)
{
    $branchManager = Auth::user();

    $branch = $branchManager->branch;

    if (!$branch) {
        return response()->json([
            'status' => 404,
            'message' => 'Branch not found'
        ], 404);
    }

    /*
    |--------------------------------------------------------------------------
    | Staff
    |--------------------------------------------------------------------------
    */

    $staff = SaleStaff::where('id', $id)
        ->where('branch_id', $branch->id)
        ->first();

    if (!$staff) {
        return response()->json([
            'status' => 404,
            'message' => 'Staff not found'
        ], 404);
    }

    $month = Carbon::now()->format('F');
    $year = Carbon::now()->year;

    /*
    |--------------------------------------------------------------------------
    | Targets
    |--------------------------------------------------------------------------
    */

    $targets = AssignedTarget::where('user_id', $staff->id)->get();

    $assigned = [
        'garments' => 0,
        'unstitched' => 0,
        'accessories' => 0
    ];

    foreach ($targets as $target) {

        $assigned[strtolower($target->category)] = $target->target;

    }

    /*
    |--------------------------------------------------------------------------
    | Achieved
    |--------------------------------------------------------------------------
    */

    $achieved = [
        'garments' => 0,
        'unstitched' => 0,
        'accessories' => 0
    ];

    $saleItems = SaleItem::where('salesperson_code', $staff->employee_id)->get();

    foreach ($saleItems as $item) {

        $qty = max(0, $item->quantity);

        $category = strtolower(trim($item->category));

        // Garments
        if (in_array($category, [
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
        ])) {

            $achieved['garments'] += $qty;

        }

        // Unstitched
        elseif (in_array($category, [
            'dupatta - dyed',
            'unstitched trousers'
        ])) {

            $achieved['unstitched'] += $qty;

        }

        // Accessories
        elseif (in_array($category, [
            'hand bag',
            'scarves - printed',
            'sunglasses',
            'jewellery',
            'clutches',
            'perfumes',
            'body mist',
            'non-tradable'
        ])) {

            $achieved['accessories'] += $qty;

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    $totalTarget = array_sum($assigned);

    $totalAchieved = array_sum($achieved);

    $remaining = max(0, $totalTarget - $totalAchieved);

    /*
    |--------------------------------------------------------------------------
    | Category Performance
    |--------------------------------------------------------------------------
    */

    $performance = [];

    foreach ($assigned as $category => $target) {

        $done = $achieved[$category];

        $percentage = $target > 0
            ? round(($done / $target) * 100)
            : 0;

        if ($percentage > 100) {
            $percentage = 100;
        }

        $performance[] = [

            'category' => ucfirst($category),

            'target' => $target,

            'achieved' => $done,

            'remaining' => max(0, $target - $done),

            'percentage' => $percentage

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return response()->json([

        'status' => 200,

        'message' => 'Staff details',

        'data' => [

            'staff_name' => $staff->name,

            'designation' => optional($staff->designation)->name,

            'branch' => $branch->name,

            'target' => $totalTarget,

            'achieved' => $totalAchieved,

            'remaining' => $remaining,

            'category_performance' => $performance

        ]

    ]);
}
}
