<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\AssignedTarget;
use App\Models\Branch;
use App\Models\BranchManager;
use App\Models\Commission;
use App\Models\FootfallDailySummary;
use App\Models\Notification;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleStaff;
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

        $rows = Notification::select('notifications.*')
            ->join('notification_targets', 'notification_targets.notification_id', '=', 'notifications.id')
            ->where('notification_targets.targetable_id', $user->id)
            ->where('notification_targets.targetable_type', get_class($user))
            ->orderBy('notifications.created_at', 'desc')
            ->addSelect('notification_targets.seen')
            ->get();

        // Role-level notifications (by user_type) for this role
        $roleUserTypes = $this->roleUserTypes($role);

        $byType = Notification::whereIn('user_type', $roleUserTypes)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $merged = $rows->concat($byType)->unique('id')->sortByDesc('created_at')->values();

        $list = [];
        $unreadCount = 0;

        foreach ($merged as $item) {
            $seen = (int) ($item->seen ?? $item->seenByUser ?? $item->is_read ?? 0);
            $isUnread = $seen == 0;

            if ($isUnread) {
                $unreadCount++;
            }

            $tag = $this->resolveNotificationTag($item->title, $item->description, $item->user_type ?? null);

            $list[] = [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'tag' => $tag,
                'image' => $item->image,
                'is_unread' => $isUnread,
                'seen' => $seen,
                'created_at' => optional($item->created_at)->toDateTimeString(),
                'time_ago' => optional($item->created_at)->diffForHumans(),
            ];
        }

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

        $type = strtolower($request->get('type', 'weekly'));

        if ($type === 'monthly') {
            $from = Carbon::now()->startOfMonth();
            $to = Carbon::now()->endOfMonth();
        } else {
            $type = 'weekly';
            $from = Carbon::now()->startOfWeek();
            $to = Carbon::now()->endOfWeek();
        }

        $month = Carbon::now()->format('F');
        $year = (string) Carbon::now()->year;

        $commissionRate = (float) (Commission::where('role', 'sales_staff')->value('commission') ?? 0);

        $branch = \App\Models\Branch::find($user->branch_id);
        $branchName = $branch->name ?? '';

        $staffMembers = SaleStaff::where('branch_id', $user->branch_id)->get();
        $rows = [];

        foreach ($staffMembers as $staff) {
            $target = (float) AssignedTarget::where('user_id', $staff->id)
                ->where('month', $month)
                ->where('year', $year)
                ->where('status', 'approved')
                ->sum('target');

            if ($target <= 0) {
                $target = (float) AssignedTarget::where('user_id', $staff->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->sum('target');
            }

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

            $achieved = $target > 0 ? min($achieved, $target) : $achieved;

            $saleAmount = $saleItems->sum(function ($item) {
                return max(0, (float) $item->quantity) * max(0, (float) $item->price);
            });

            $commission = $target > 0
                ? round($saleAmount * ($commissionRate / 100), 2)
                : 0;

            $percentage = $target > 0
                ? min(100, (int) round(($achieved / $target) * 100))
                : 0;

            $rows[] = [
                'staff_id' => $staff->id,
                'name' => $staff->name,
                'is_you' => (int) $staff->id === (int) $user->id,
                'target' => $target,
                'achieved' => $achieved,
                'achievement_percentage' => $percentage,
                'remaining_percentage' => $target > 0 ? (100 - $percentage) : 0,
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
