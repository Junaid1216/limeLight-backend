<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CommissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Sale;
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
        $monthVariants = array_values(array_unique([
            $month,
            strtolower($month),
            ucfirst(strtolower($month)),
        ]));
        $yearVariants = array_values(array_unique([$year, (string) $year]));

        $categories = ['garments', 'unstitched', 'accessories'];

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

        /*
        |--------------------------------------------------------------------------
        | Overall Target + Achieved (same as admin Reporting Branch Category Overall)
        | target = sum(category monthly_target)
        | achieved_raw = sum(category qty)
        | achieved = min(target, achieved_raw)
        |--------------------------------------------------------------------------
        */

        $targets = Target::where('branch_id', $branch->id)
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->get();

        $from = Carbon::now()->startOfMonth()->startOfDay();
        $to = Carbon::now()->endOfMonth()->endOfDay();

        $saleItems = SaleItem::with('sale')
            ->whereHas('sale', function ($q) use ($branch, $from, $to) {
                $q->where('shop_name', $branch->name)
                    ->whereBetween('date', [$from, $to]);
            })->get();

        $monthlyTarget = 0.0;
        $achievedRaw = 0.0;

        foreach ($categories as $category) {
            $targetRecord = $targets->first(function ($t) use ($category) {
                return strtolower(trim((string) $t->category)) === $category;
            });
            $catTarget = (float) ($targetRecord->monthly_target ?? 0);
            $monthlyTarget += $catTarget;

            $mapping = $categoryMappings[$category] ?? [];
            foreach ($saleItems as $item) {
                $itemCategory = strtolower(trim((string) ($item->category ?? '')));
                if (in_array($itemCategory, $mapping, true)) {
                    $achievedRaw += max(0, (int) $item->quantity);
                }
            }
        }

        $isAssigned = $monthlyTarget > 0;
        $achieved = $isAssigned ? min($monthlyTarget, $achievedRaw) : 0;
        $remaining = $isAssigned ? max($monthlyTarget - $achieved, 0) : 0;

        /*
        |--------------------------------------------------------------------------
        | Commission: identical to Sales History branchManagerSales
        | Load sales → dedupe legacy duplicates → resolveSaleItems →
        | (price − discount) × qty × designation rate @ sale date
        | Round per invoice, then sum
        |--------------------------------------------------------------------------
        */

        $commission = 0.0;
        $sales = Sale::with([
            'items:id,sale_key,invoice_id,quantity,tax,price,discount,salesperson_name,salesperson_code,shop_name',
            'itemsByInvoice:id,sale_key,invoice_id,quantity,tax,price,discount,salesperson_name,salesperson_code,shop_name',
        ])
            ->where('shop_name', $branch->name)
            ->whereRaw('DATE(`date`) BETWEEN ? AND ?', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->get(['id', 'sales_id', 'invoice_id', 'sale_key', 'shop_name', 'date', 'net_total']);

        $sales = $this->dedupeSales($sales);
        $rateCache = [];

        foreach ($sales as $sale) {
            $items = $this->resolveSaleItems($sale);
            if ($items->isEmpty()) {
                continue;
            }

            $dateKey = (string) $sale->date;
            if (!array_key_exists($dateKey, $rateCache)) {
                $rateCache[$dateKey] = CommissionHelper::rateForBranchManager($user, $sale->date);
            }
            $rate = $rateCache[$dateKey];

            $invoiceCommission = round($items->sum(function ($item) use ($rate) {
                $price = max(0, (float) $item->price);
                $discount = max(0, (float) $item->discount);
                $quantity = (int) $item->quantity;
                $salesAmount = ($price - $discount) * $quantity;

                return ($salesAmount * $rate) / 100;
            }), 2);

            $commission += $invoiceCommission;
        }

        $commission = round($commission, 2);

        /*
        |--------------------------------------------------------------------------
        | Percentage (same as admin Overall)
        |--------------------------------------------------------------------------
        */

        $achievedPercentage = $isAssigned
            ? (int) min(100, round(($achieved / $monthlyTarget) * 100))
            : 0;

        $remainingPercentage = $isAssigned ? (100 - $achievedPercentage) : 0;

        return response()->json([
            'status' => 200,
            'message' => 'Dashboard loaded successfully',
            'data' => [
                'branch_monthly_target' => $isAssigned ? (int) $monthlyTarget : 0,
                'achieved' => $isAssigned ? (int) $achieved : 0,
                'remaining' => $isAssigned ? (int) $remaining : 0,
                'commission' => $commission,
                'achieved_percentage' => $achievedPercentage,
                'remaining_percentage' => $remainingPercentage,
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
        | Missed pieces from a completed week carry into the next week's target
        | (same idea as weekly_over_achieved, but for under-achievement).
        |--------------------------------------------------------------------------
        */

        $weekly = [];
        $weeklyActual = [];
        $weeklyOverAchieved = [];
        $weeklyBaseTargets = [];
        $weeklyEffectiveTargets = [];
        $weeklyCarryForward = [];

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();
        $now = Carbon::now();

        // Remaining from a finished week that rolls into the next week
        $carryForward = 0.0;

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
            | Weekly Achieved (actual)
            |--------------------------------------------------------------------------
            */

            $weekAchievedActual = 0;

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

                    $weekAchievedActual += $qty;

                }

            }

            /*
            |--------------------------------------------------------------------------
            | Base week target + carry-in from previous missed week
            | week_N is % of monthly_target → pieces = monthly * week% / 100
            |--------------------------------------------------------------------------
            */

            $weekPercent = (float) ($target->{'week_' . $i} ?? 0);
            $weekTargetPieces = ($target->monthly_target * $weekPercent) / 100;
            $carryIn = $carryForward;
            $effectiveWeekTarget = $weekTargetPieces + $carryIn;

            $weekAchievedCapped = $effectiveWeekTarget > 0
                ? min($weekAchievedActual, $effectiveWeekTarget)
                : 0;

            $weekOverAchieved = max(0, $weekAchievedActual - $effectiveWeekTarget);

            /*
            |--------------------------------------------------------------------------
            | Only completed weeks push remaining into the next week
            |--------------------------------------------------------------------------
            */

            $weekCompleted = $now->gt($end);
            if ($weekCompleted) {
                $carryForward = max(0, $effectiveWeekTarget - $weekAchievedActual);
            } else {
                // Current / future week: do not roll remaining forward yet
                $carryForward = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | Store Weekly Performance
            |--------------------------------------------------------------------------
            */

            $weeklyBaseTargets["week{$i}"] = round($weekTargetPieces, 2);
            $weeklyCarryForward["week{$i}"] = round($carryIn, 2);
            $weeklyEffectiveTargets["week{$i}"] = round($effectiveWeekTarget, 2);
            $weekly["week{$i}"] = $weekAchievedCapped;
            $weeklyActual["week{$i}"] = $weekAchievedActual;
            $weeklyOverAchieved["week{$i}"] = $weekOverAchieved;

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

            // Original week % targets (no carry)
            'weekly_base_targets' => [
                'week1' => $weeklyBaseTargets['week1'],
                'week2' => $weeklyBaseTargets['week2'],
                'week3' => $weeklyBaseTargets['week3'],
                'week4' => $weeklyBaseTargets['week4'],
            ],

            // Missed qty brought into each week from the previous completed week
            'weekly_carry_forward' => [
                'week1' => $weeklyCarryForward['week1'],
                'week2' => $weeklyCarryForward['week2'],
                'week3' => $weeklyCarryForward['week3'],
                'week4' => $weeklyCarryForward['week4'],
            ],

            // What the week must achieve now = base + carry (shown as weekly_targets)
            'weekly_targets' => [
                'week1' => $weeklyEffectiveTargets['week1'],
                'week2' => $weeklyEffectiveTargets['week2'],
                'week3' => $weeklyEffectiveTargets['week3'],
                'week4' => $weeklyEffectiveTargets['week4'],
            ],

            // Capped at effective weekly target (base + carry)
            'weekly_performance' => [
                'week1' => $weekly['week1'],
                'week2' => $weekly['week2'],
                'week3' => $weekly['week3'],
                'week4' => $weekly['week4'],
            ],

            // Actual achieved (can be above target)
            'weekly_actual_performance' => [
                'week1' => $weeklyActual['week1'],
                'week2' => $weeklyActual['week2'],
                'week3' => $weeklyActual['week3'],
                'week4' => $weeklyActual['week4'],
            ],

            // Extra above effective weekly target (0 if not exceeded)
            'weekly_over_achieved' => [
                'week1' => $weeklyOverAchieved['week1'],
                'week2' => $weeklyOverAchieved['week2'],
                'week3' => $weeklyOverAchieved['week3'],
                'week4' => $weeklyOverAchieved['week4'],
            ],
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

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $branch = $user->branch;

        if (!$branch) {
            return response()->json([
                'status' => 404,
                'message' => 'Branch not found',
            ], 404);
        }

        $month = Carbon::now()->format('F');
        $year = Carbon::now()->year;
        $monthVariants = array_values(array_unique([
            $month,
            strtolower($month),
            ucfirst(strtolower($month)),
        ]));
        $yearVariants = array_values(array_unique([$year, (string) $year]));

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

        $targets = Target::where('branch_id', $branch->id)
            ->whereIn('month', $monthVariants)
            ->whereIn('year', $yearVariants)
            ->get()
            ->keyBy(function ($t) {
                return strtolower(trim((string) $t->category));
            });

        $from = Carbon::now()->startOfMonth()->startOfDay();
        $to = Carbon::now()->endOfMonth()->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Same BM commission as Sales History / branchDashboard:
        | sales → dedupe → resolveSaleItems →
        | (price − discount) × qty × designation rate @ sale date
        | Round per invoice, then sum — split by category bucket
        |--------------------------------------------------------------------------
        */

        $sales = Sale::with([
            'items:id,sale_key,invoice_id,quantity,tax,price,discount,category,salesperson_name,salesperson_code,shop_name',
            'itemsByInvoice:id,sale_key,invoice_id,quantity,tax,price,discount,category,salesperson_name,salesperson_code,shop_name',
        ])
            ->where('shop_name', $branch->name)
            ->whereRaw('DATE(`date`) BETWEEN ? AND ?', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->get(['id', 'sales_id', 'invoice_id', 'sale_key', 'shop_name', 'date', 'net_total']);

        $sales = $this->dedupeSales($sales);

        $achievedByCategory = [
            'garments' => 0,
            'unstitched' => 0,
            'accessories' => 0,
        ];
        $commissionByCategory = [
            'garments' => 0.0,
            'unstitched' => 0.0,
            'accessories' => 0.0,
        ];
        $rateCache = [];

        foreach ($sales as $sale) {
            $items = $this->resolveSaleItems($sale);
            if ($items->isEmpty()) {
                continue;
            }

            $dateKey = (string) $sale->date;
            if (!array_key_exists($dateKey, $rateCache)) {
                $rateCache[$dateKey] = CommissionHelper::rateForBranchManager($user, $sale->date);
            }
            $rate = $rateCache[$dateKey];

            $invoiceByCategory = [
                'garments' => 0.0,
                'unstitched' => 0.0,
                'accessories' => 0.0,
            ];

            foreach ($items as $item) {
                $itemCategory = strtolower(trim((string) ($item->category ?? '')));
                $bucket = null;
                foreach ($categoryMappings as $category => $mapping) {
                    if (in_array($itemCategory, $mapping, true)) {
                        $bucket = $category;
                        break;
                    }
                }
                if ($bucket === null) {
                    continue;
                }

                $qty = max(0, (int) $item->quantity);
                $achievedByCategory[$bucket] += $qty;

                $price = max(0, (float) $item->price);
                $discount = max(0, (float) $item->discount);
                $salesAmount = ($price - $discount) * $qty;
                $invoiceByCategory[$bucket] += ($salesAmount * $rate) / 100;
            }

            foreach ($invoiceByCategory as $category => $amount) {
                if ($amount != 0.0) {
                    $commissionByCategory[$category] += round($amount, 2);
                }
            }
        }

        $response = [];

        foreach (array_keys($categoryMappings) as $category) {
            $targetRecord = $targets->get($category);
            $monthlyTarget = (float) ($targetRecord->monthly_target ?? 0);

            if (!$targetRecord || $monthlyTarget <= 0) {
                continue;
            }

            $achievedRaw = $achievedByCategory[$category];
            $achieved = min($monthlyTarget, $achievedRaw);

            $response[] = [
                'category' => ucfirst($category),
                'target' => $monthlyTarget,
                'achieved' => $achieved,
                'commission' => round($commissionByCategory[$category], 2),
            ];
        }

        return response()->json([
            'status' => 200,
            'message' => 'Commission retrieved successfully',
            'data' => $response,
        ]);
    }

    /**
     * Prefer sale_key items when present; otherwise fall back to invoice_id (legacy rows).
     * Same logic as SalesHistoryController::resolveSaleItems.
     */
    private function resolveSaleItems(Sale $sale)
    {
        $hasKey = $sale->sale_key !== null && $sale->sale_key !== '';

        if ($hasKey) {
            $items = $sale->relationLoaded('items') ? $sale->items : $sale->items()->get();
            if ($items->isNotEmpty()) {
                return $items;
            }
        }

        $items = $sale->relationLoaded('itemsByInvoice')
            ? $sale->itemsByInvoice
            : $sale->itemsByInvoice()->get();

        $keyed = $items->filter(function ($item) {
            return $item->sale_key !== null && $item->sale_key !== '';
        });

        if ($keyed->isNotEmpty()) {
            if ($sale->shop_name) {
                $byShop = $keyed->filter(function ($item) use ($sale) {
                    return empty($item->shop_name) || $item->shop_name === $sale->shop_name;
                });
                if ($byShop->isNotEmpty()) {
                    return $byShop->values();
                }
            }

            return $keyed->values();
        }

        return $items->filter(function ($item) {
            return $item->sale_key === null || $item->sale_key === '';
        })->values();
    }

    /**
     * Same shop+invoice can exist twice (legacy NULL sale_key + synced sale_key).
     * Keep the keyed row; keep NULL-key only when no keyed sibling exists.
     */
    private function dedupeSales($sales)
    {
        $keyedPairs = [];
        foreach ($sales as $sale) {
            if ($sale->sale_key !== null && $sale->sale_key !== '') {
                $keyedPairs[$this->saleDedupeKey($sale)] = true;
            }
        }

        return $sales->filter(function ($sale) use ($keyedPairs) {
            $hasKey = $sale->sale_key !== null && $sale->sale_key !== '';
            if ($hasKey) {
                return true;
            }

            return !isset($keyedPairs[$this->saleDedupeKey($sale)]);
        })->values();
    }

    private function saleDedupeKey(Sale $sale): string
    {
        return strtolower(trim((string) $sale->shop_name)) . '|' . trim((string) $sale->invoice_id);
    }
}
