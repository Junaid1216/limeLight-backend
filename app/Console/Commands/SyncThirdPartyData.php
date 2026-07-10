<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\FootfallDailySummary;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TransactionSummary;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Log;

class SyncThirdPartyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:thirdparty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Sales, Footfall and Transaction data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
        $branches = Branch::all();

        $start = Carbon::now()->subWeek()->startOfWeek();
        $end = Carbon::now()->subWeek()->endOfWeek();

        foreach ($branches as $branch) {

            $this->info("Starting {$branch->branch_id}");

            $this->info("Transaction...");
            $this->syncTransactionSummary($branch,$start,$end);

            $this->info("Footfall...");
            $this->syncFootfall($branch,$start,$end);

            $this->info("Sales...");
            $this->syncSales($branch,$start,$end);

            $this->info("Finished {$branch->branch_id}");
        }

        $this->info('Completed');
    }

    private function syncTransactionSummary($branch,$start,$end)
{
    $url="https://unov.yofi.link/api/outlet/".$branch->branch_id."/";

    $response=Http::withHeaders([
        'Authorization'=>'Token cf8bc76e6373efe9027e1ee50ddb483fa46458c7'
    ])->get($url);

    if(!$response->successful()){
        return;
    }

    $data=$response->json();

    foreach($data['transaction_summary'] as $item){

        TransactionSummary::updateOrCreate(

            [
                'branch_id'=>$branch->id,
                'day'=>$item['day']
            ],

            [
                'total_transactions'=>$item['total_transactions'],
                'total_sales'=>$item['total_sales'],
                'total_items'=>$item['total_items']
            ]

        );

    }
}

private function syncFootfall($branch, $start, $end)
{
    $url = "https://unov.yofi.link/api/footfall/daily/";

    $response = Http::get($url, [
        'outlet' => $branch->branch_id,
        'token' => 'cf8bc76e6373efe9027e1ee50ddb483fa46458c7',
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d')
    ]);

    if (!$response->successful()) {
        return;
    }

    $data = $response->json();

    foreach ($data as $shop) {

        if (!isset($shop['footfall_daily_summary'])) {
            continue;
        }

        foreach ($shop['footfall_daily_summary'] as $row) {

            FootfallDailySummary::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'date' => $row['date'],
                ],
                [
                    'footfall' => $row['footfall'],
                    'on_time' => $row['on_time'],
                ]
            );
        }
    }
}

private function syncSales($branch,$start,$end)
{
    $url="http://202.141.241.251:96/api/Sales/GetSalesByDateV2";

    $response=Http::get($url,[

        'AppId'=>10,

        'AppKey'=>'jgiDwu3HwlKgbS9qorWmsVzhJ4oP0s5j',

        'SaleFromDate'=>$start->format('Y-m-d 00:00:00'),

        'SaleToDate'=>$end->format('Y-m-d 23:59:59')

    ]);

    if(!$response->successful()){
        return;
    }

    $data=$response->json();

    if(empty($data['SalesList'])){
        return;
    }

    foreach($data['SalesList'] as $sale){

        if($sale['ShopName']!=$branch->name){
            continue;
        }

        $invoice=Sale::updateOrCreate(

            [
                'invoice_id'=>$sale['InvoiceNo']
            ],

            [
                'invoice_id'           => $sale['InvoiceNo'],
                'sale_from_date'       => $sale['SaleFromDate'],
                'sale_to_date'         => $sale['SaleToDate'],
                'date'                 => Carbon::parse($sale['Date']),
                'coupon_no'            => $sale['CouponNo'],
                'shop_id'              => $sale['ShopId'],
                'shop_name'            => $sale['ShopName'],
                'mobile_number'        => $sale['MobileNumber'],
                'customer_name'        => $sale['CustomerName'],
                'gender'               => $sale['Gender'],
                'net_total'            => $sale['NetTotal'],
                'comments'             => $sale['Comments'],
                'additional_comments'  => $sale['AdditionalComments'],
            ]

        );

        foreach($sale['data'] as $item){

            SaleItem::updateOrCreate(

                [
                    'invoice_id'=>$invoice->invoice_id,

                    'product_code'=>$item['ProductCode']
                ],

                [
                    'product_name'=>$item['ProductName'],

                    'product_category'=>$item['ProductCategory'],

                    'product_sub_category'=>$item['ProductSubCategory'],

                    'size'=>$item['Size'],

                    'technical_details'=>$item['TechnicalDetails'],

                    'color'=>$item['Color'],

                    'quantity'=>$item['Quantity'],

                    'price'=>$item['Price'],

                    'discount'=>$item['Discount'],

                    'tax'=>$item['Tax'],

                    'salesperson_name'=>$item['SalesPersonName'],

                    'salesperson_code'=>$item['SalesPersonCode'],

                    'category'=>$item['Category']

                ]

            );

        }

    }
}
}
