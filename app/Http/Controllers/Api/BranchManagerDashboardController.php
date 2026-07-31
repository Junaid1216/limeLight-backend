<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\SaleItem;
use App\Models\Target;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchManagerDashboardController extends Controller
{
    public function branchDashboard()
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

        $month = Carbon::now()->format('F');
        $year  = Carbon::now()->year;

        $categoryMappings = [
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
                'tops'
            ],
            'unstitched' => [
                'dupatta - dyed',
                'unstitched trousers'
            ],
            'accessories' => [
                'hand bag',
                'scarves - printed',
                'sunglasses',
                'jewellery',
                'clutches',
                'perfumes',
                'body mist',
                'non-tradable'
            ]
        ];

        /*
        |--------------------------------------------------------------------------
        | Monthly Target + Achieved (same quantity logic as categoryPerformance)
        |--------------------------------------------------------------------------
        */

        $targets = Target::where('branch_id', $branch->id)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $monthlyTarget = $targets->sum('monthly_target');
        $achieved = 0;

        $saleItems = SaleItem::whereHas('sale', function ($q) use ($branch) {
            $q->where('shop_name', $branch->name)
                ->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year);
        })->get();

        foreach ($targets as $target) {
            $category = strtolower(trim($target->category));
            $mapping = $categoryMappings[$category] ?? [];

            if (empty($mapping)) {
                continue;
            }

            $categoryAchieved = 0;

            foreach ($saleItems as $item) {
                $itemCategory = strtolower(trim($item->category));
                $qty = max(0, $item->quantity);

                if (in_array($itemCategory, $mapping)) {
                    $categoryAchieved += $qty;
                }
            }

            // Same as categoryPerformance: cap per category
            $achieved += min($categoryAchieved, $target->monthly_target);
        }

        /*
        |--------------------------------------------------------------------------
        | Remaining
        |--------------------------------------------------------------------------
        */

        $remaining = max($monthlyTarget - $achieved, 0);

        /*
        |--------------------------------------------------------------------------
        | Commission
        |--------------------------------------------------------------------------
        */

        $commission = round($achieved * 0.05, 2);

        /*
        |--------------------------------------------------------------------------
        | Percentage
        |--------------------------------------------------------------------------
        */

        $achievedPercentage = $monthlyTarget > 0
            ? round(($achieved / $monthlyTarget) * 100, 2)
            : 0;

        if ($achievedPercentage > 100) {
            $achievedPercentage = 100;
        }

        $remainingPercentage = 100 - $achievedPercentage;

        return response()->json([
            'status' => 200,
            'message' => 'Dashboard loaded successfully',
            'data' => [
                'branch_monthly_target' => $monthlyTarget,
                'achieved' => $achieved,
                'remaining' => $remaining,
                'commission' => $commission,
                'achieved_percentage' => $achievedPercentage,
                'remaining_percentage' => $remainingPercentage
            ]
        ]);
    }

    public function categoryPerformance()
    {
        $user = Auth::user();

        $branch = $user->branch;

        $month = Carbon::now()->format('F');
        $year = Carbon::now()->year;

        $categories = [
            'garments',
            'unstitched',
            'accessories'
        ];

        $response = [];

        $categoryMappings = [

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
            'tops'
        ],

        'unstitched' => [
            'dupatta - dyed',
            'unstitched trousers'
        ],

        'accessories' => [
            'hand bag',
            'scarves - printed',
            'sunglasses',
            'jewellery',
            'clutches',
            'perfumes',
            'body mist',
            'non-tradable'
        ]

        ];

            foreach ($categories as $category) {

                /*
                |--------------------------------------------------------------------------
                | Target
                |--------------------------------------------------------------------------
                */

                $target = Target::where('branch_id',$branch->id)
                    ->where('category',$category)
                    ->where('month',$month)
                    ->where('year',$year)
                    ->first();

                if(!$target){
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Achieved
                |--------------------------------------------------------------------------
                */

                        $achieved = 0;

                        $saleItems = SaleItem::whereHas('sale', function ($q) use ($branch) {
                            $q->where('shop_name', $branch->name)
                                ->whereMonth('date', Carbon::now()->month)
                                ->whereYear('date', Carbon::now()->year);
                        })->get();

                        foreach ($saleItems as $item) {

                            $itemCategory = strtolower(trim($item->category));
                            $qty = max(0, $item->quantity);

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
                | Achieved Cannot Be Greater Than Monthly Target
                |--------------------------------------------------------------------------
                */

                $achieved = min(
                    $achieved,
                    $target->monthly_target
                );

            /*
        |--------------------------------------------------------------------------
        | Weekly Performance
        |--------------------------------------------------------------------------
        */

        $weekly = [];

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        for ($i = 1; $i <= 4; $i++) {

            /*
            |--------------------------------------------------------------------------
            | Week Start & End
            |--------------------------------------------------------------------------
            */

            $start = $monthStart->copy()
                ->addDays(($i - 1) * 7)
                ->startOfDay();

            $end = $start->copy()
                ->addDays(6)
                ->endOfDay();


            /*
            |--------------------------------------------------------------------------
            | Make Sure Week Does Not Go Outside Current Month
            | Week 4 covers remaining days till month end
            |--------------------------------------------------------------------------
            */

            if ($start->lt($monthStart)) {
                $start = $monthStart->copy();
            }

            if ($i === 4 || $end->gt($monthEnd)) {
                $end = $monthEnd->copy()->endOfDay();
            }


            /*
            |--------------------------------------------------------------------------
            | Weekly Achieved (actual products sold — not capped by weekly target)
            |--------------------------------------------------------------------------
            */

            $weekAchieved = 0;

            $weekItems = SaleItem::whereHas('sale', function ($q) use (
                $branch,
                $start,
                $end
            ) {

                $q->where('shop_name', $branch->name)
                    ->whereBetween('date', [
                        $start,
                        $end
                    ]);

            })->get();


            foreach ($weekItems as $item) {

                $itemCategory = strtolower(
                    trim($item->category)
                );

                $qty = max(
                    0,
                    $item->quantity
                );

                if (
                    in_array(
                        $itemCategory,
                        $categoryMappings[$category]
                    )
                ) {

                    $weekAchieved += $qty;

                }

            }

            /*
            |--------------------------------------------------------------------------
            | Store Weekly Performance
            |--------------------------------------------------------------------------
            */

            $weekly["week{$i}"] = $weekAchieved;

        }

                $remaining = max($target->monthly_target - $achieved, 0);

        $percentage = $target->monthly_target > 0
            ? round(($achieved / $target->monthly_target) * 100)
            : 0;

        if ($percentage > 100) {
            $percentage = 100;
        }

        $response[] = [
            'category' => ucfirst($category),

            'target' => $target->monthly_target,

            'achieved' => $achieved,

            'remaining' => $remaining,

            'achievement_percentage' => $percentage,

            'weekly_targets' => [
                'week1' => $target->week_1,
                'week2' => $target->week_2,
                'week3' => $target->week_3,
                'week4' => $target->week_4,
            ],

            'weekly_performance' => [
                'week1' => $weekly['week1'],
                'week2' => $weekly['week2'],
                'week3' => $weekly['week3'],
                'week4' => $weekly['week4'],
            ]
        ];

            }

            return response()->json([

                'status'=>200,

                'message'=>'Category performance',

                'data'=>$response

            ]);
    }

    public function commission()
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

        $commissionRate = Commission::where('role', 'branch_manager')
        ->value('commission') ?? 0;

        $categories = [
            'garments',
            'unstitched',
            'accessories'
        ];

        $response = [];

        foreach ($categories as $category) {

            $target = Target::where('branch_id',$branch->id)
                ->where('category',$category)
                ->where('month',$month)
                ->where('year',$year)
                ->first();

            if(!$target){
                continue;
            }

            $achieved = 0;
                    $commission = 0;

                    $saleItems = SaleItem::whereHas('sale', function ($q) use ($branch) {
                        $q->where('shop_name', $branch->name)
                            ->whereMonth('date', Carbon::now()->month)
                            ->whereYear('date', Carbon::now()->year);
                    })->get();

                    foreach ($saleItems as $item) {

                        $itemCategory = strtolower(trim($item->category));

                        $qty = max(0, $item->quantity);

                        $saleAmount = $item->price * $qty;

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

                            $commission += ($saleAmount * $commissionRate) / 100;

                        }

                        elseif (
                            $category == 'unstitched' &&
                            in_array($itemCategory, [
                                'dupatta - dyed',
                                'unstitched trousers'
                            ])
                        ) {

                            $achieved += $qty;

                            $commission += ($saleAmount * $commissionRate) / 100;

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

                            $commission += ($saleAmount * $commissionRate) / 100;

                        }
                    }

            /*
            |--------------------------------------------------------------------------
            | Commission
            |--------------------------------------------------------------------------
            */

            $commission = 0;

            if($target->monthly_target > 0){

                $percentage = ($achieved / $target->monthly_target) * 100;

                if($percentage >= 100){

                    // Example calculation
                    $commission = $achieved * 10;

                }else{

                    $commission = round($achieved * 5);

                }
            }

            $response[] = [

                'category' => ucfirst($category),

                'target' => $target->monthly_target,

                'achieved' => $achieved,

                'commission' => round($commission, 2)

            ];
        }

        return response()->json([

            'status' => 200,

            'message' => 'Commission retrieved successfully',

            'data' => $response

        ]);
    }
}
