<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignedTarget;
use App\Models\Slab;
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
        | Third Party API
        |--------------------------------------------------------------------------
        */

        $fromDate = '2026-07-09 08:00:00';
        $toDate   = '2026-07-15 23:59:59';

        $url = "http://202.141.241.251:96/api/Sales/GetSalesByDateV2?" . http_build_query([
            'AppId'        => 10,
            'AppKey'       => 'jgiDwu3HwlKgbS9qorWmsVzhJ4oP0s5j',
            'SaleFromDate' => $fromDate,
            'SaleToDate'   => $toDate,
        ]);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            return response()->json([
                'status' => 500,
                'message' => curl_error($curl),
            ], 500);
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $response = json_decode($response, true);

        if ($httpCode != 200) {
            return response()->json([
                'status' => $httpCode,
                'message' => 'Unable to fetch sales data',
                'response' => $response,
            ], $httpCode);
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
            'garments'=>0,
            'unstitched'=>0,
            'accessories'=>0
        ];

        $totalSale = 0;

        if(isset($response['SalesList'])){

            foreach($response['SalesList'] as $invoice){

                foreach($invoice['data'] as $item){

                    /*
                     Ignore returned products
                    */

                    $qty = max(0,$item['Quantity']);

                    /*
                     Only authenticated staff sales
                    */

                    if($item['SalesPersonCode'] != $user->employee_id){
                        continue;
                    }

                    $totalSale += $qty;

                    $category = strtolower($item['Category']);

                    /*
                    |--------------------------------------------------------------------------
                    | Garments Mapping
                    |--------------------------------------------------------------------------
                    */

                    if(
                        in_array($category,[
                           'Signature',
                            'Flowy',
                            'Trouser',
                            'Regular Prints',
                            'Fusion Co-Ords',
                            'Festive',
                            'Composed Rotary',
                            'Premium',
                            'Casual',
                            'Glam',
                            'Dailywear',
                            'Regular Running',
                            'Regular Panel',
                            'Modish',
                            'Trendy',
                            'Premium Wear',
                            'Tops'
                        ])
                    ){
                        $sold['garments'] += $qty;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Unstitched Mapping
                    |--------------------------------------------------------------------------
                    */

                    elseif(
                        in_array($category,[
                            'Dupatta - Dyed',
                            'Unstitched Trousers'
                        ])
                    ){
                        $sold['unstitched'] += $qty;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Accessories Mapping
                    |--------------------------------------------------------------------------
                    */

                    elseif(
                        in_array($category,[
                            'Hand Bag',
                            'Scarves - Printed',
                            'Sunglasses',
                            'Jewellery',
                            'Clutches',
                            'Perfumes',
                            'Body Mist',
                            'Non-Tradable'
                        ])
                    ){
                        $sold['accessories'] += $qty;
                    }

                }

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
        | Third Party API
        |--------------------------------------------------------------------------
        */

        $url = "http://202.141.241.251:96/api/Sales/GetSalesByDateV2?"
            . "AppId=10"
            . "&AppKey=jgiDwu3HwlKgbS9qorWmsVzhJ4oP0s5j"
            . "&SaleFromDate=2026-07-08 08:00:00"
            . "&SaleToDate=2026-07-15 23:59:59";

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => str_replace(' ', '%20', $url),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

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

        if (isset($response['SalesList'])) {

            foreach ($response['SalesList'] as $sale) {

                foreach ($sale['data'] as $item) {

                    if ($item['SalesPersonCode'] != $user->employee_id) {
                        continue;
                    }

                    $qty = max(0, $item['Quantity']);

                    $category = strtolower(trim($item['Category']));

                    // Garments
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

                    // Unstitched
                    elseif (in_array($category, [
                        'unstitched',
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

    /*
    |--------------------------------------------------------------------------
    | Third Party API
    |--------------------------------------------------------------------------
    */

    $url = "http://202.141.241.251:96/api/Sales/GetSalesByDateV2?"
            . "AppId=10"
            . "&AppKey=jgiDwu3HwlKgbS9qorWmsVzhJ4oP0s5j"
            . "&SaleFromDate=2026-07-08 08:00:00"
            . "&SaleToDate=2026-07-15 23:59:59";

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => str_replace(' ', '%20', $url),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);
    

    $records = [];

    if(isset($response['SalesList']))
    {
        foreach($response['SalesList'] as $sale)
        {
            /*
            ---------------------------------------------------------
            Check whether invoice belongs to logged in Sales Staff
            ---------------------------------------------------------
            */

            $belongsToStaff = false;

            foreach($sale['data'] as $item)
            {
                if($item['SalesPersonCode'] == $user->employee_id)
                {
                    $belongsToStaff = true;
                    break;
                }
            }

            if(!$belongsToStaff){
                continue;
            }

            /*
            ---------------------------------------------------------
            Find Slab
            ---------------------------------------------------------
            */

            $slab = Slab::where('from_amount','<=',$sale['NetTotal'])
                        ->where('to_amount','>=',$sale['NetTotal'])
                        ->first();

            $records[] = [

                'date' => date(
                    'd M Y',
                    strtotime($sale['Date'])
                ),

                'slab' => $slab->slab_name ?? '-',

                'invoice_no' => $sale['InvoiceNo'],

                'sales_id' => $user->employee_id,

                'net_sale' => $sale['NetTotal'],

                'incentive' => $slab->incentive_amount ?? 0

            ];
        }
    }

    return response()->json([
        'status'=>200,
        'message'=>'Slip bound incentives',
        'data'=>$records
    ]);
}

}
