<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CommissionHelper;
use App\Http\Controllers\Controller;
use App\Models\AssignedTarget;
use App\Models\FootfallDailySummary;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleStaff;
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

        $now = Carbon::now();
        $month = $now->format('F');
        $year = (string) $now->year;
        $monthNum = $now->month;

        $monthVariants = array_values(array_unique([
            $month,
            strtolower($month),
            ucfirst(strtolower($month)),
            $now->format('m'),
            (string) $monthNum,
        ]));

        $yearVariants = array_values(array_unique([
            $year,
            (int) $year,
        ]));

        /*
        |--------------------------------------------------------------------------
        | Assigned Targets (current month)
        |--------------------------------------------------------------------------
        */

        // Only admin-approved targets count as assigned
        $targets = AssignedTarget::where('user_id', $user->id)
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->where('status', 'approved')
            ->get();

        $assigned = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

        foreach ($targets as $target) {
            $category = strtolower(trim((string) $target->category));

            if (array_key_exists($category, $assigned)) {
                $assigned[$category] = max(0, (float) $target->target);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Sold Quantities (current month)
        |--------------------------------------------------------------------------
        */

        $sold = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

        $totalSale = 0;
        $saleAmount = 0;

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

        $saleItems = SaleItem::with('sale')
            ->where('salesperson_code', (string) $user->employee_id)
            ->whereHas('sale', function ($q) use ($branch, $monthNum, $year) {
                $q->where('shop_name', $branch->name)
                    ->whereMonth('date', $monthNum)
                    ->whereYear('date', $year);
            })
            ->get(['invoice_id', 'category', 'quantity', 'price']);

        $commissionTotal = 0;

        foreach ($saleItems as $item) {
            $qty = max(0, (float) $item->quantity);
            $itemCategory = strtolower(trim((string) $item->category));

            foreach ($categoryMappings as $category => $mapping) {
                if (in_array($itemCategory, $mapping, true)) {
                    $sold[$category] += $qty;
                    $totalSale += $qty;
                    $saleAmount += $qty * max(0, (float) $item->price);
                    $commissionTotal += CommissionHelper::forProduct(
                        'sales_staff',
                        $qty,
                        $item->price,
                        optional($item->sale)->date
                    );
                    break;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Target Vs Achievement
        |--------------------------------------------------------------------------
        */

        $dashboard = [];
        $totalAchieved = 0;

        foreach ($assigned as $category => $target) {
            $achieved = min($sold[$category], $target);
            $totalAchieved += $achieved;

            $percentage = $target > 0
                ? min(100, (int) round(($achieved / $target) * 100))
                : 0;

            $dashboard[] = [
                'category' => ucfirst($category),
                'target' => $target,
                'achieved' => $achieved,
                'achieved_percentage' => $percentage,
                'remaining_percentage' => $target > 0 ? (100 - $percentage) : 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Commission (current month)
        |--------------------------------------------------------------------------
        */

        $totalTarget = array_sum($assigned);

        $commission = $totalTarget > 0
            ? round($commissionTotal, 2)
            : 0;

        $commissionPercentage = $totalTarget > 0
            ? min(100, (int) round(($totalAchieved / $totalTarget) * 100))
            : 0;

        return response()->json([
            'status' => 200,
            'message' => 'Dashboard loaded successfully',
            'data' => [
                'month' => $month,
                'year' => $year,
                'target_vs_achievement' => $dashboard,
                'commission' => [
                    'target' => $totalTarget,
                    'sale' => $totalAchieved,
                    'commission' => $commission,
                    'achieved_percentage' => $commissionPercentage,
                    'remaining_percentage' => $totalTarget > 0 ? (100 - $commissionPercentage) : 0,
                ],
            ],
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

        $branch = $user->branch;

        if (!$branch) {
            return response()->json([
                'status' => 404,
                'message' => 'Branch not found'
            ], 404);
        }

        $now = Carbon::now();
        $month = $now->format('F');
        $year = (string) $now->year;
        $monthNum = $now->month;

        $monthVariants = array_values(array_unique([
            $month,
            strtolower($month),
            ucfirst(strtolower($month)),
            $now->format('m'),
            (string) $monthNum,
        ]));

        $yearVariants = array_values(array_unique([
            $year,
            (int) $year,
        ]));

        /*
        |--------------------------------------------------------------------------
        | Assigned Targets (current month)
        |--------------------------------------------------------------------------
        */

        // Only admin-approved targets count as assigned
        $targets = AssignedTarget::where('user_id', $user->id)
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->where('status', 'approved')
            ->get();

        $assigned = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

        foreach ($targets as $target) {
            $category = strtolower(trim((string) $target->category));

            if (array_key_exists($category, $assigned)) {
                $assigned[$category] = max(0, (float) $target->target);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Achieved Quantities (current month)
        |--------------------------------------------------------------------------
        */

        $achieved = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

        $saleAmountByCategory = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

        $categoryMappings = [
            'garments' => [
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
                'composed rotary',
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

        $saleItems = SaleItem::with('sale')
            ->where('salesperson_code', (string) $user->employee_id)
            ->whereHas('sale', function ($q) use ($branch, $monthNum, $year) {
                $q->where('shop_name', $branch->name)
                    ->whereMonth('date', $monthNum)
                    ->whereYear('date', $year);
            })
            ->get(['invoice_id', 'category', 'quantity', 'price']);

        $commissionByCategory = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];

        foreach ($saleItems as $item) {
            $qty = max(0, (float) $item->quantity);
            $itemCategory = strtolower(trim((string) $item->category));
            $lineAmount = $qty * max(0, (float) $item->price);

            foreach ($categoryMappings as $category => $mapping) {
                if (in_array($itemCategory, $mapping, true)) {
                    $achieved[$category] += $qty;
                    $saleAmountByCategory[$category] += $lineAmount;
                    $commissionByCategory[$category] += CommissionHelper::forProduct(
                        'sales_staff',
                        $qty,
                        $item->price,
                        optional($item->sale)->date
                    );
                    break;
                }
            }
        }

        $data = [];

        foreach ($assigned as $category => $target) {
            $sale = min($achieved[$category], $target);

            $commission = $target > 0
                ? round($commissionByCategory[$category], 2)
                : 0;

            $data[] = [
                'category' => ucfirst($category),
                'target' => $target,
                'achieved' => $sale,
                'commission' => $commission,
            ];
        }

        return response()->json([
            'status' => 200,
            'message' => 'Category breakdown retrieved successfully',
            'data' => $data,
        ]);
    }

    public function slipBoundIncentive()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();

        $records = [];

        // Current month invoices where this sales staff is on the slip
        $sales = Sale::whereHas('items', function ($q) use ($user) {
                $q->where('salesperson_code', $user->employee_id);
            })
            ->whereRaw('DATE(`date`) BETWEEN ? AND ?', [$monthStart, $monthEnd])
            ->with(['items' => function ($q) use ($user) {
                $q->where('salesperson_code', $user->employee_id);
            }])
            ->orderByDesc('date')
            ->get();

        foreach ($sales as $sale) {
            $netTotal = (float) $sale->net_total;

            // Match admin slab against this invoice net total
            $slab = Slab::whereRaw('CAST(from_amount AS DECIMAL(12,2)) <= ?', [$netTotal])
                ->whereRaw('CAST(to_amount AS DECIMAL(12,2)) >= ?', [$netTotal])
                ->orderByRaw('CAST(from_amount AS DECIMAL(12,2)) ASC')
                ->first();

            if (!$slab) {
                continue;
            }

            $records[] = [
                'date' => Carbon::parse($sale->date)->format('d M Y'),
                'slab' => $slab->slab_name ?? '-',
                'invoice_id' => $sale->invoice_id,
                'sales_id' => $user->employee_id,
                'net_sale' => $netTotal,
                'incentive' => (float) ($slab->incentive_amount ?? 0),
            ];
        }

        return response()->json([
            'status' => 200,
            'message' => 'Slip bound incentives',
            'from' => $monthStart,
            'to' => $monthEnd,
            'data' => $records,
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

public function staffComparison(Request $request)
{
    $user = Auth::user();

    if (!$user || !$user->branch_id) {
        return response()->json([
            'status' => 404,
            'message' => 'Branch not found'
        ], 404);
    }

    $branch = $user->branch;
    $branchName = $branch->name ?? '';

    $type = strtolower($request->type ?? 'monthly');

    if ($type === 'monthly') {
        $from = Carbon::now()->startOfMonth();
        $to   = Carbon::now()->endOfMonth();
    } else {
        $type = 'weekly';
        $from = Carbon::now()->startOfWeek();
        $to   = Carbon::now()->endOfWeek();
    }

    $now = Carbon::now();
    $month = $now->format('F');
    $year = (string) $now->year;
    $monthNum = $now->month;

    $monthVariants = array_values(array_unique([
        $month,
        strtolower($month),
        ucfirst(strtolower($month)),
        $now->format('m'),
        (string) $monthNum,
    ]));

    $yearVariants = array_values(array_unique([
        $year,
        (int) $year,
    ]));

    $staffMembers = SaleStaff::where('branch_id', $user->branch_id)->get();

    $rows = [];

    foreach ($staffMembers as $staff) {

        /*
        |--------------------------------------------------------------------------
        | Assigned Target (current month)
        |--------------------------------------------------------------------------
        */

        // Only admin-approved targets count as assigned
        $target = (float) AssignedTarget::where('user_id', $staff->id)
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->where('status', 'approved')
            ->sum('target');

        /*
        |--------------------------------------------------------------------------
        | Achieved Quantity (selected period + same branch)
        |--------------------------------------------------------------------------
        */

        $saleItems = SaleItem::where('salesperson_code', (string) $staff->employee_id)
            ->whereHas('sale', function ($q) use ($from, $to, $branchName) {
                $q->where('shop_name', $branchName)
                    ->whereBetween('date', [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]);
            })
            ->get(['quantity', 'price']);

        $achieved = (float) $saleItems->sum(function ($item) {
            return max(0, (float) $item->quantity);
        });

        // Achieved cannot exceed target; no target => achieved 0
        $achieved = $target > 0 ? min($achieved, $target) : 0;

        /*
        |--------------------------------------------------------------------------
        | Commission (always current month, only when target assigned)
        |--------------------------------------------------------------------------
        */

        $monthlySaleItems = SaleItem::with('sale')
            ->where('salesperson_code', (string) $staff->employee_id)
            ->whereHas('sale', function ($q) use ($branchName, $monthNum, $year) {
                $q->where('shop_name', $branchName)
                    ->whereMonth('date', $monthNum)
                    ->whereYear('date', $year);
            })
            ->get(['invoice_id', 'quantity', 'price']);

        $commission = $target > 0
            ? CommissionHelper::sumProducts('sales_staff', $monthlySaleItems)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Percentage
        |--------------------------------------------------------------------------
        */

        $percentage = $target > 0
            ? min(100, (int) round(($achieved / $target) * 100))
            : 0;

        $rows[] = [
            'staff_id' => $staff->id,
            'name' => $staff->name,
            'target' => $target,
            'achieved' => $achieved,
            'achievement_percentage' => $percentage,
            'remaining_percentage' => $target > 0 ? (100 - $percentage) : 0,
            'commission' => $commission,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Ranking
    |--------------------------------------------------------------------------
    */

    usort($rows, function ($a, $b) {
        return $b['achievement_percentage'] <=> $a['achievement_percentage'];
    });

    foreach ($rows as $index => &$row) {
        $row['rank'] = $index + 1;
    }
    unset($row);

    /*
    |--------------------------------------------------------------------------
    | Logged-in Staff
    |--------------------------------------------------------------------------
    */

    $yourData = collect($rows)->firstWhere('staff_id', $user->id);

    $others = collect($rows)
        ->where('staff_id', '!=', $user->id)
        ->take(6)
        ->values();

    return response()->json([
        'status' => 200,
        'message' => 'Staff Comparison',
        'data' => [
            'type' => $type,
            'your_data' => $yourData,
            'staff' => $others,
        ],
    ]);
}

}
