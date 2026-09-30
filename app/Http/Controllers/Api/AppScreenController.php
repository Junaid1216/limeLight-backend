<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CommissionHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\AssignedTarget;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\FootfallDailySummary;
use App\Models\Notification;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleStaff;
use App\Models\Target;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppScreenController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Notifications by role (staff | manager | asm)
    | Screenshot 1
    |--------------------------------------------------------------------------
    */
    public function getNotifications(Request $request)
    {
        $request->validate([
            'role' => 'required|in:staff,manager,asm',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $role = $request->role;
        $expectedClass = $this->roleModelClass($role);

        if (!$user instanceof $expectedClass) {
            return response()->json([
                'status' => 403,
                'message' => 'Logged-in user does not match the requested role',
            ], 403);
        }

        $list = [];
        $unreadCount = 0;

        // Admin-sent notifications (admin_notifications + user_notifications)
        $adminSent = UserNotification::query()
            ->with('adminNotification')
            ->where('user_id', $user->id)
            ->where('user_type', $role)
            ->whereHas('adminNotification', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('is_deleted', 0)->orWhereNull('is_deleted');
                });
            })
            ->latest()
            ->get();

        foreach ($adminSent as $row) {
            $admin = $row->adminNotification;
            if (!$admin) {
                continue;
            }

            $seen = (int) $row->is_read;
            $isUnread = $seen === 0;
            if ($isUnread) {
                $unreadCount++;
            }

            $list[] = [
                'id' => 'admin_' . $row->id,
                'admin_notification_id' => $row->admin_notification_id,
                'title' => $admin->title,
                'description' => $admin->description,
                'tag' => $this->resolveNotificationTag($admin->title, $admin->description, $row->user_type),
                'image' => null,
                'is_unread' => $isUnread,
                'seen' => $seen,
                'source' => 'admin_notification',
                'created_at' => optional($row->created_at)->toDateTimeString(),
                'time_ago' => optional($row->created_at)->diffForHumans(),
            ];
        }

        // Legacy targeted notifications
        $rows = Notification::select('notifications.*')
            ->join('notification_targets', 'notification_targets.notification_id', '=', 'notifications.id')
            ->where('notification_targets.targetable_id', $user->id)
            ->where('notification_targets.targetable_type', get_class($user))
            ->where(function ($q) {
                $q->where('notifications.delete_by_admin', 0)
                    ->orWhereNull('notifications.delete_by_admin');
            })
            ->orderBy('notifications.created_at', 'desc')
            ->addSelect('notification_targets.seen')
            ->get();

        // Role-level notifications (by user_type) for this role
        $roleUserTypes = $this->roleUserTypes($role);

        $byType = Notification::whereIn('user_type', $roleUserTypes)
            ->where(function ($q) {
                $q->where('delete_by_admin', 0)->orWhereNull('delete_by_admin');
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $merged = $rows->concat($byType)->unique('id')->sortByDesc('created_at')->values();

        foreach ($merged as $item) {
            $seen = (int) ($item->seen ?? $item->seenByUser ?? $item->is_read ?? 0);
            $isUnread = $seen == 0;

            if ($isUnread) {
                $unreadCount++;
            }

            $tag = $this->resolveNotificationTag($item->title, $item->description, $item->user_type ?? null);

            $list[] = [
                'id' => $item->id,
                'admin_notification_id' => null,
                'title' => $item->title,
                'description' => $item->description,
                'tag' => $tag,
                'image' => $item->image,
                'is_unread' => $isUnread,
                'seen' => $seen,
                'source' => 'legacy',
                'created_at' => optional($item->created_at)->toDateTimeString(),
                'time_ago' => optional($item->created_at)->diffForHumans(),
            ];
        }

        usort($list, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        return response()->json([
            'status' => 200,
            'message' => 'Notifications retrieved successfully',
            'data' => [
                'role' => $role,
                'unread_count' => $unreadCount,
                'recent' => $list,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update profile — Sales Staff / Branch Manager / ASM
    | Screenshot 2
    |--------------------------------------------------------------------------
    */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'role' => 'required|in:staff,manager,asm',
            'name' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
        ]);

        $user = Auth::user();

        if (!$user) {
            return ResponseHelper::error(null, 'User not authenticated', 'error', 401);
        }

        $role = $request->role;
        $expectedClass = $this->roleModelClass($role);

        if (!$user instanceof $expectedClass) {
            return ResponseHelper::error(null, 'Logged-in user does not match the requested role', 'error', 403);
        }

        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('admin/assets/images/users'), $filename);
            $user->image = 'public/admin/assets/images/users/' . $filename;
        }

        $user->save();
        $user->refresh();

        $designation = \App\Models\Designation::find($user->designation_id);

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id ?? null,
            'image' => $user->image,
            'role' => $this->roleLabel($role),
            'designation' => $designation->name ?? null,
        ];

        if ($user instanceof SaleStaff || $user instanceof BranchManager) {
            $branch = \App\Models\Branch::find($user->branch_id);
            $data['branch_name'] = $branch->name ?? null;
        }

        if ($user instanceof AreaSaleManager) {
            $region = \App\Models\Region::find($user->region_id);
            $data['region_name'] = $region->name ?? null;
        }

        return ResponseHelper::success($data, 'Profile updated successfully', '200', 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Sales Staff — Staff Comparison (Weekly / Monthly)
    | Screenshot 3
    |--------------------------------------------------------------------------
    */
    public function salesStaffComparison(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof SaleStaff) {
            return response()->json([
                'status' => 403,
                'message' => 'Only sales staff can access this API',
            ], 403);
        }

        if (!$user->branch_id) {
            return response()->json([
                'status' => 404,
                'message' => 'Branch not found',
            ], 404);
        }

        $now = Carbon::now();
        $type = strtolower($request->get('type', 'weekly'));

        if ($type === 'monthly') {
            $from = $now->copy()->startOfMonth()->startOfDay();
            $to = $now->copy()->endOfMonth()->endOfDay();
        } else {
            $type = 'weekly';
            [$from, $to] = $this->currentWeekRangeOfMonth();
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

        $branch = Branch::find($user->branch_id);
        $branchName = $branch->name ?? '';

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

        $staffMembers = SaleStaff::where('branch_id', $user->branch_id)
            ->get(['id', 'name', 'employee_id', 'branch_id']);

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
        if (!empty($employeeIds) && $branchName !== '') {
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

        $rows = [];
        $rateCache = [];

        foreach ($staffMembers as $staff) {
            /*
            |--------------------------------------------------------------------------
            | Target / Achieved — same as admin sale staff reporting
            | garments / unstitched / accessories; cap per category then sum
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
            | Commission — Sales History formula
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

            $percentage = $isAssigned
                ? (int) min(100, round(($achieved / $target) * 100))
                : 0;

            $rows[] = [
                'staff_id' => $staff->id,
                'name' => $staff->name,
                'is_you' => (int) $staff->id === (int) $user->id,
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

        $yourData = collect($rows)->firstWhere('is_you', true);
        $others = collect($rows)->where('is_you', false)->values();

        return response()->json([
            'status' => 200,
            'message' => 'Staff Comparison',
            'data' => [
                'type' => $type,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'your_data' => $yourData,
                'staff' => $others,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Sales Staff — Conversion Rate (Footfall vs Invoices)
    | Screenshot 3
    |--------------------------------------------------------------------------
    */
    public function salesStaffConversionRate(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof SaleStaff) {
            return response()->json([
                'status' => 403,
                'message' => 'Only sales staff can access this API',
            ], 403);
        }

        $branch = Branch::find($user->branch_id);

        if (!$branch) {
            return response()->json([
                'status' => 404,
                'message' => 'Branch not found',
            ], 404);
        }

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::today()->subDays(6)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::today()->endOfDay();

        if ($from->gt($to)) {
            return response()->json([
                'status' => 422,
                'message' => 'From date cannot be greater than To date',
            ], 422);
        }

        $footfalls = FootfallDailySummary::where('branch_id', $branch->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(function ($row) {
                return Carbon::parse($row->date)->format('Y-m-d');
            });

        $sales = Sale::where('shop_name', $branch->name)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(function ($sale) {
                return Carbon::parse($sale->date)->format('Y-m-d');
            });

        $chart = [];
        $peak = [
            'date' => null,
            'label' => null,
            'conversion_rate' => 0,
            'footfall' => 0,
            'invoices' => 0,
        ];

        $current = $from->copy()->startOfDay();

        while ($current->lte($to)) {
            $date = $current->format('Y-m-d');
            $footfall = (int) (optional($footfalls->get($date))->footfall ?? 0);
            $invoiceCount = isset($sales[$date]) ? $sales[$date]->count() : 0;

            $conversion = $footfall > 0
                ? round(($invoiceCount / $footfall) * 100, 2)
                : 0;

            if ($conversion > $peak['conversion_rate']) {
                $peak = [
                    'date' => $date,
                    'label' => $current->format('H:i'),
                    'conversion_rate' => $conversion,
                    'footfall' => $footfall,
                    'invoices' => $invoiceCount,
                ];
            }

            $chart[] = [
                'date' => $date,
                'label' => $current->format('d M'),
                'footfall' => $footfall,
                'invoices' => $invoiceCount,
                'conversion_rate' => $conversion,
            ];

            $current->addDay();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Conversion rate fetched successfully',
            'data' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'peak' => $peak,
                'chart' => $chart,
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

    private function roleModelClass(string $role): string
    {
        $map = [
            'staff' => SaleStaff::class,
            'manager' => BranchManager::class,
            'asm' => AreaSaleManager::class,
        ];

        return $map[$role];
    }

    private function roleUserTypes(string $role): array
    {
        $map = [
            'staff' => ['staff', 'sale_staff', 'sales_staff', 'SaleStaff'],
            'manager' => ['manager', 'branch_manager', 'BranchManager'],
            'asm' => ['asm', 'area_sale_manager', 'AreaSaleManager'],
        ];

        return $map[$role] ?? [$role];
    }

    private function roleLabel(string $role): string
    {
        $map = [
            'staff' => 'Sales Staff',
            'manager' => 'Branch Manager',
            'asm' => 'ASM',
        ];

        return $map[$role] ?? $role;
    }

    private function resolveNotificationTag(?string $title, ?string $description, $userType = null): string
    {
        $text = strtolower(trim(($title ?? '') . ' ' . ($description ?? '')));

        if (strpos($text, 'target') !== false || strpos($text, 'approval') !== false) {
            return 'Target';
        }
        if (strpos($text, 'staff') !== false || strpos($text, 'behind') !== false) {
            return 'Staff';
        }
        if (strpos($text, 'branch') !== false || strpos($text, 'performance') !== false) {
            return 'Branch';
        }
        if (strpos($text, 'feedback') !== false) {
            return 'Feedback';
        }
        if (strpos($text, 'survey') !== false) {
            return 'Surveys';
        }

        return $userType ? ucfirst((string) $userType) : 'General';
    }
}
