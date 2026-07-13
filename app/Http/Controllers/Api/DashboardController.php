<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignedTarget;
use App\Models\FootfallDailySummary;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Slab;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
     public function dashboard()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'User not authenticated'
            ], 401);
        }

        $branch = $user->branch;

        if (!$branch) {
            return response()->json([
                'status' => 404,
                'message' => 'Branch not found'
            ], 404);
        }

        

        /*
        |--------------------------------------------------------------------------
        | Get Assigned Targets
        |--------------------------------------------------------------------------
        */

        $targets = AssignedTarget::where('user_id',$user->id)->get();

        $assigned = [
            'garments'=>0,
            'unstitched'=>0,
            'accessories'=>0
        ];

        foreach($targets as $target){

            $assigned[strtolower($target->category)] = $target->target;

        }

        /*
        |--------------------------------------------------------------------------
        | Sold Quantities
        |--------------------------------------------------------------------------
        */

                $sold = [
                'garments' => 0,
                'unstitched' => 0,
                'accessories' => 0
            ];

            $totalSale = 0;

            $saleItems = SaleItem::where('salesperson_code', $user->employee_id)->get();

            foreach ($saleItems as $item) {

                $qty = max(0, $item->quantity);

                $totalSale += $qty;

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

                    $sold['garments'] += $qty;
                }

                // Unstitched
                elseif (in_array($category, [
                    'dupatta - dyed',
                    'unstitched trousers'
                ])) {

                    $sold['unstitched'] += $qty;
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

                    $sold['accessories'] += $qty;
                }
            }

            
        /*
        |--------------------------------------------------------------------------
        | Target Vs Achievement
        |--------------------------------------------------------------------------
        */

        $dashboard = [];

        foreach($assigned as $category=>$target){

            $achieved = $sold[$category];

            $percentage = $target > 0
                ? round(($achieved/$target)*100)
                : 0;

            if($percentage>100){
                $percentage=100;
            }

            $dashboard[]=[

                'category'=>ucfirst($category),

                'target'=>$target,

                'achieved'=>$achieved,

                'achieved_percentage'=>$percentage,

                'remaining_percentage'=>100-$percentage

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Commission
        |--------------------------------------------------------------------------
        */

        $totalTarget = array_sum($assigned);

        $commission = $totalSale * 150; // Example

        $commissionPercentage = $totalTarget>0
            ? round(($totalSale/$totalTarget)*100)
            :0;

        if($commissionPercentage>100){
            $commissionPercentage=100;
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status'=>200,

            'message'=>'Dashboard loaded successfully',

            'data'=>[

                'target_vs_achievement'=>$dashboard,

                'commission'=>[

                    'target'=>$totalTarget,

                    'sale'=>$totalSale,

                    'commission'=>$commission,

                    'achieved_percentage'=>$commissionPercentage,

                    'remaining_percentage'=>100-$commissionPercentage

                ]

            ]

        ]);

    }

    public function categoryBreakdown()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'User not authenticated'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Assigned Targets
        |--------------------------------------------------------------------------
        */

        $targets = AssignedTarget::where('user_id', $user->id)->get();

        $assigned = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

        foreach ($targets as $target) {

            $assigned[strtolower($target->category)] = $target->target;

        }

        
        /*
        |--------------------------------------------------------------------------
        | Achieved Quantities
        |--------------------------------------------------------------------------
        */

            $achieved = [
                'garments' => 0,
                'unstitched' => 0,
                'accessories' => 0,
            ];

            $saleItems = SaleItem::where('salesperson_code', $user->employee_id)->get();

            foreach ($saleItems as $item) {

                $qty = max(0, $item->quantity);

                $category = strtolower(trim($item->category));

                if (in_array($category, [
                    'signature',
                    'flowy',
                    'trouser',
                    'tops',
                    'casual',
                    'premium',
                    'festive',
                    'glam',
                    'dailywear',
                    'modish',
                    'trendy',
                    'regular prints',
                    'regular running',
                    'regular panel',
                    'premium wear',
                    'fusion co-ords',
                    'composed rotary'
                ])) {

                    $achieved['garments'] += $qty;
                }

                elseif (in_array($category, [
                    'dupatta - dyed',
                    'unstitched trousers'
                ])) {

                    $achieved['unstitched'] += $qty;
                }

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
        | Commission
        |--------------------------------------------------------------------------
        */

        $ratePerProduct = 10;

        $data = [];

        foreach ($assigned as $category => $target) {

            $sale = $achieved[$category];

            $commission = $sale * $ratePerProduct;

            $data[] = [
                'category' => ucfirst($category),
                'target' => $target,
                'achieved' => $sale,
                'commission' => $commission
            ];
        }

        return response()->json([
            'status' => 200,
            'message' => 'Category breakdown retrieved successfully',
            'data' => $data
        ]);
    }

    public function slipBoundIncentive()
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => 401,
            'message' => 'Unauthenticated'
        ],401);
    }

    
        $records = [];

        $sales = Sale::whereHas('items', function ($q) use ($user) {
            $q->where('salesperson_code', $user->employee_id);
        })->with(['items' => function ($q) use ($user) {
            $q->where('salesperson_code', $user->employee_id);
        }])->get();

        foreach ($sales as $sale) {

            $slab = Slab::where('from_amount', '<=', $sale->net_total)
                ->where('to_amount', '>=', $sale->net_total)
                ->first();
            
            $records[] = [

                'date' => Carbon::parse($sale->date)->format('d M Y'),

                'slab' => $slab->slab_name ?? '-',

                'invoice_id' => $sale->invoice_id,

                'sales_id' => $user->employee_id,

                'net_sale' => $sale->net_total,

                'incentive' => $slab->incentive_amount ?? 0

            ];
        }

    return response()->json([
        'status'=>200,
        'message'=>'Slip bound incentives',
        'data'=>$records
    ]);
}

public function conversionRate(Request $request)
{
    $user = Auth::user();
    
    $branch = $user->branch;

    if (!$branch) {
        return response()->json([
            'status' => 404,
            'message' => 'Branch not found'
        ],404);
    }

    $from = $request->from
        ? Carbon::parse($request->from)->startOfDay()
        : Carbon::today()->subDays(6)->startOfDay();

    $to = $request->to
        ? Carbon::parse($request->to)->endOfDay()
        : Carbon::today()->endOfDay();

    /*
    |--------------------------------------------------------------------------
    | Footfall
    |--------------------------------------------------------------------------
    */

    $footfalls = FootfallDailySummary::where('branch_id',$branch->id)
        ->whereBetween('date',[
            $from->toDateString(),
            $to->toDateString()
        ])
        ->get()
        ->keyBy('date');

    /*
    |--------------------------------------------------------------------------
    | Invoices
    |--------------------------------------------------------------------------
    */

    $sales = Sale::where('shop_name',$branch->name)
        ->whereBetween('date',[$from,$to])
        ->get()
        ->groupBy(function($sale){
            return Carbon::parse($sale->date)->format('Y-m-d');
        });

    $chart = [];

    $peak = [
        'date' => null,
        'conversion_rate' => 0,
        'footfall' => 0,
        'invoices' => 0
    ];

    $current = $from->copy();

    while($current <= $to){

        $date = $current->format('Y-m-d');

        $footfall = optional($footfalls->get($date))->footfall ?? 0;

        $invoiceCount = isset($sales[$date])
            ? $sales[$date]->count()
            : 0;

        $conversion = $footfall > 0
            ? round(($invoiceCount/$footfall)*100,2)
            : 0;

        if($conversion > $peak['conversion_rate']){

            $peak = [
                'date'=>$date,
                'conversion_rate'=>$conversion,
                'footfall'=>$footfall,
                'invoices'=>$invoiceCount
            ];

        }

        $chart[] = [

            'date'=>$date,

            'footfall'=>$footfall,

            'invoices'=>$invoiceCount,

            'conversion_rate'=>$conversion

        ];

        $current->addDay();

    }

    return response()->json([

        'status'=>200,

        'message'=>'Conversion rate fetched successfully',

        'data'=>[

            'from'=>$from->toDateString(),

            'to'=>$to->toDateString(),

            'peak'=>$peak,

            'chart'=>$chart

        ]

    ]);

}

}
