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
use App\Models\Target;
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

        $from = $now->copy()->startOfMonth()->startOfDay();
        $to = $now->copy()->endOfMonth()->endOfDay();

        $saleItems = SaleItem::query()
            ->select([
                'sale_items.invoice_id',
                'sale_items.category',
                'sale_items.quantity',
                'sale_items.price',
                'sale_items.discount',
                'sales.date',
            ])
            ->join('sales', 'sales.invoice_id', '=', 'sale_items.invoice_id')
            ->where('sale_items.salesperson_code', (string) $user->employee_id)
            ->where('sales.shop_name', $branch->name)
            ->whereBetween('sales.date', [$from, $to])
            ->get();

        foreach ($saleItems as $item) {
            $qty = max(0, (float) $item->quantity);
            $itemCategory = strtolower(trim((string) $item->category));

            foreach ($categoryMappings as $category => $mapping) {
                if (in_array($itemCategory, $mapping, true)) {
                    $sold[$category] += $qty;
                    $totalSale += $qty;
                    $price = max(0, (float) $item->price);
                    $discount = max(0, (float) $item->discount);
                    $saleAmount += ($price - $discount) * $qty;
                    break;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Commission — same as Sales History sale staff
        | (price − discount) × qty × sales_staff rate @ sale date
        | Round per invoice, then sum
        |--------------------------------------------------------------------------
        */

        $commissionTotal = 0.0;
        $rateCache = [];

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

            $commissionTotal += $invoiceCommission;
        }

        $commissionTotal = round($commissionTotal, 2);

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

        $from = $now->copy()->startOfMonth()->startOfDay();
        $to = $now->copy()->endOfMonth()->endOfDay();

        $saleItems = SaleItem::query()
            ->select([
                'sale_items.invoice_id',
                'sale_items.category',
                'sale_items.quantity',
                'sale_items.price',
                'sale_items.discount',
                'sales.date',
            ])
            ->join('sales', 'sales.invoice_id', '=', 'sale_items.invoice_id')
            ->where('sale_items.salesperson_code', (string) $user->employee_id)
            ->where('sales.shop_name', $branch->name)
            ->whereBetween('sales.date', [$from, $to])
            ->get();

        $commissionByCategory = [
            'garments' => 0.0,
            'unstitched' => 0.0,
            'accessories' => 0.0,
        ];
        $rateCache = [];

        foreach ($saleItems->groupBy('invoice_id') as $invoiceItems) {
            $saleDate = $invoiceItems->first()->date;
            $dateKey = (string) $saleDate;
            if (!array_key_exists($dateKey, $rateCache)) {
                $rateCache[$dateKey] = CommissionHelper::rateFor('sales_staff', $saleDate);
            }
            $rate = $rateCache[$dateKey];

            $invoiceByCategory = [
                'garments' => 0.0,
                'unstitched' => 0.0,
                'accessories' => 0.0,
            ];

            foreach ($invoiceItems as $item) {
                $qty = max(0, (float) $item->quantity);
                $itemCategory = strtolower(trim((string) $item->category));
                $price = max(0, (float) $item->price);
                $discount = max(0, (float) $item->discount);
                $lineAmount = ($price - $discount) * $qty;

                foreach ($categoryMappings as $category => $mapping) {
                    if (in_array($itemCategory, $mapping, true)) {
                        $achieved[$category] += $qty;
                        $saleAmountByCategory[$category] += $lineAmount;
                        $invoiceByCategory[$category] += ($lineAmount * $rate) / 100;
                        break;
                    }
                }
            }

            foreach ($invoiceByCategory as $category => $amount) {
                if ($amount != 0.0) {
                    $commissionByCategory[$category] += round($amount, 2);
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
    $now = Carbon::now();

    if ($type === 'weekly') {
        [$from, $to] = $this->currentWeekRangeOfMonth();
        $type = 'weekly';
    } else {
        $type = 'monthly';
        $from = $now->copy()->startOfMonth()->startOfDay();
        $to = $now->copy()->endOfMonth()->endOfDay();
    }

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

    $staffMembers = SaleStaff::where('branch_id', $user->branch_id)->get();

    $branchTargets = Target::where('branch_id', $user->branch_id)
        ->whereIn('month', array_values(array_unique([
            $month,
            strtolower($month),
            ucfirst(strtolower($month)),
        ])))
        ->whereIn('year', $yearVariants)
        ->get();

    $employeeIds = $staffMembers->pluck('employee_id')
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
                'sale_items.price',
                'sale_items.discount',
                'sales.date',
            ])
            ->join('sales', 'sales.invoice_id', '=', 'sale_items.invoice_id')
            ->where('sales.shop_name', $branchName)
            ->whereBetween('sales.date', [$from, $to])
            ->whereIn('sale_items.salesperson_code', $employeeIds)
            ->get();

        $itemsByStaff = $items->groupBy(function ($item) {
            return (string) $item->salesperson_code;
        });
    }

    // Commission always uses full current month
    $monthFrom = $now->copy()->startOfMonth()->startOfDay();
    $monthTo = $now->copy()->endOfMonth()->endOfDay();
    $monthlyItemsByStaff = collect();
    if (!empty($employeeIds)) {
        $monthlyItems = SaleItem::query()
            ->select([
                'sale_items.invoice_id',
                'sale_items.salesperson_code',
                'sale_items.quantity',
                'sale_items.price',
                'sale_items.discount',
                'sales.date',
            ])
            ->join('sales', 'sales.invoice_id', '=', 'sale_items.invoice_id')
            ->where('sales.shop_name', $branchName)
            ->whereBetween('sales.date', [$monthFrom, $monthTo])
            ->whereIn('sale_items.salesperson_code', $employeeIds)
            ->get();

        $monthlyItemsByStaff = $monthlyItems->groupBy(function ($item) {
            return (string) $item->salesperson_code;
        });
    }

    $rows = [];
    $rateCache = [];

    foreach ($staffMembers as $staff) {
        /*
        |--------------------------------------------------------------------------
        | Target / Achieved — same as admin sale staff + BM staff comparison
        | garments / unstitched / accessories only; cap per category then sum
        |--------------------------------------------------------------------------
        */

        $targets = AssignedTarget::where('user_id', $staff->id)
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->where('status', 'approved')
            ->get();

        $assigned = [
            'garments' => 0.0,
            'unstitched' => 0.0,
            'accessories' => 0.0,
        ];

        foreach ($targets as $assignedTarget) {
            $key = strtolower(trim((string) $assignedTarget->category));
            if (isset($assigned[$key])) {
                $assigned[$key] += max(0, (float) $assignedTarget->target);
            }
        }

        foreach ($assigned as $category => $value) {
            $assigned[$category] = $this->resolveStaffPeriodTarget($value, $branchTargets, $type);
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

        /*
        |--------------------------------------------------------------------------
        | Commission (always current month) — Sales History formula
        |--------------------------------------------------------------------------
        */

        $commission = 0.0;
        if ($isAssigned) {
            $monthlySaleItems = $monthlyItemsByStaff->get((string) $staff->employee_id, collect());

            foreach ($monthlySaleItems->groupBy('invoice_id') as $invoiceItems) {
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

        $percentage = $isAssigned
            ? (int) min(100, round(($achieved / $target) * 100))
            : 0;

        $rows[] = [
            'staff_id' => $staff->id,
            'name' => $staff->name,
            'target' => $isAssigned ? $target : 0,
            'achieved' => $isAssigned ? $achieved : 0,
            'achievement_percentage' => $percentage,
            'remaining_percentage' => $isAssigned ? (100 - $percentage) : 0,
            'commission' => $commission,
        ];
    }

    usort($rows, function ($a, $b) {
        return $b['achievement_percentage'] <=> $a['achievement_percentage'];
    });

    foreach ($rows as $index => &$row) {
        $row['rank'] = $index + 1;
    }
    unset($row);

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

    private function resolveStaffPeriodTarget(float $monthlyAssigned, $branchTargets, string $period): float
    {
        if ($monthlyAssigned <= 0) {
            return 0;
        }

        if ($period !== 'weekly') {
            return $monthlyAssigned;
        }

        $weekColumn = 'week_' . $this->currentWeekOfMonth();

        if ($branchTargets->isEmpty()) {
            $weekPercent = 25;
        } else {
            $weekPercent = (float) $branchTargets->avg($weekColumn);
            if ($weekPercent <= 0) {
                $weekPercent = 25;
            }
        }

        return round(($monthlyAssigned * $weekPercent) / 100, 2);
    }

}
