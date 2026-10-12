<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Announcement;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\SaleStaff;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
    public function index()
    {
        $categories = Announcement::categories();
        $roleOptions = Announcement::roleOptions();

        $announcementRows = Announcement::latest()->get()->map(function ($item) use ($roleOptions) {
            return [
                'kind' => 'announcement',
                'id' => $item->id,
                'type_label' => 'Announcement',
                'category' => $item->category,
                'category_label' => $item->category_label,
                'title' => $item->title,
                'description' => $item->description,
                'roles' => collect($item->roles ?? [])->map(function ($role) use ($roleOptions) {
                    return $roleOptions[$role] ?? $role;
                })->implode(', '),
                'users' => '-',
                'status' => $item->status ? 'Active' : 'Inactive',
                'status_active' => (bool) $item->status,
                'created_at' => $item->created_at,
                'edit' => [
                    'id' => $item->id,
                    'category' => $item->category,
                    'title' => $item->title,
                    'description' => $item->description,
                    'roles' => $item->roles ?? [],
                    'date' => optional($item->announcement_date)->format('Y-m-d'),
                    'status' => $item->status ? 1 : 0,
                ],
                'delete_route' => route('announcement.destroy', $item->id),
            ];
        });

        $notificationRows = AdminNotification::with('userNotifications')
            ->where(function ($q) {
                $q->where('is_deleted', 0)->orWhereNull('is_deleted');
            })
            ->latest()
            ->get()
            ->map(function ($item) use ($roleOptions) {
                $userNames = $item->userNotifications->map(function ($row) {
                    $user = $row->resolveUser();
                    return $user ? $user->name : null;
                })->filter()->values();

                $userTypeMap = [
                    'staff' => 'Sales Staff',
                    'manager' => 'Branch Manager',
                    'asm' => 'ASM',
                    'all' => 'All',
                ];

                $rolesLabel = collect($item->roles ?? [])->map(function ($role) use ($roleOptions) {
                    return $roleOptions[$role] ?? $role;
                })->implode(', ');

                if ($rolesLabel === '') {
                    $rolesLabel = $userTypeMap[$item->user_type] ?? ucfirst((string) $item->user_type);
                }

                return [
                    'kind' => 'notification',
                    'id' => $item->id,
                    'type_label' => 'Notification',
                    'category' => null,
                    'category_label' => $userTypeMap[$item->user_type] ?? ucfirst((string) $item->user_type),
                    'title' => $item->title,
                    'description' => $item->description,
                    'roles' => $rolesLabel,
                    'users' => $userNames->implode(', ') ?: '-',
                    'status' => 'Sent',
                    'status_active' => true,
                    'created_at' => $item->created_at,
                    'edit' => null,
                    'delete_route' => route('announcement.notification.destroy', $item->id),
                ];
            });

        $rows = $announcementRows
            ->concat($notificationRows)
            ->sortByDesc(function ($row) {
                return optional($row['created_at'])->timestamp ?? 0;
            })
            ->values();

        return view('admin.announcement.index', compact('rows', 'categories', 'roleOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:announcement,notification',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'roles' => 'required|array|min:1',
            'roles.*' => 'in:asm,branch_manager,sales_staff',
        ]);

        if ($request->type === 'notification') {
            return $this->storeNotification($request);
        }

        $request->validate([
            'category' => 'required|in:hr,performance,promotions',
        ]);

        $announcement = Announcement::create([
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'roles' => $request->roles,
            'status' => 1,
        ]);

        NotificationHelper::sendToRoles(
            (array) $request->roles,
            $request->title,
            $request->description,
            [
                'type' => 'announcement',
                'source' => 'announcement',
                'announcement_id' => (string) $announcement->id,
            ]
        );

        return redirect()->route('announcement.index')->with('success', 'Announcement Created Successfully');
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'category' => 'required|in:hr,performance,promotions',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'roles' => 'required|array|min:1',
            'roles.*' => 'in:asm,branch_manager,sales_staff',
            'status' => 'nullable|boolean',
        ]);

        $announcement->update([
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description,
            'roles' => $request->roles,
            'status' => $request->has('status') ? (bool) $request->status : $announcement->status,
        ]);

        return redirect()->route('announcement.index')->with('success', 'Announcement Updated Successfully');
    }

    public function delete($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return redirect()->route('announcement.index')->with('success', 'Announcement Deleted Successfully');
    }

    public function deleteNotification($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->is_deleted = 1;
        $notification->save();

        return redirect()->route('announcement.index')->with('success', 'Notification Deleted Successfully');
    }

    /**
     * Users for notification recipient dropdown, filtered by selected roles.
     */
    public function usersByRoles(Request $request)
    {
        $roles = array_values(array_filter((array) $request->input('roles', [])));
        $users = [];

        if (in_array('sales_staff', $roles, true)) {
            foreach (SaleStaff::orderBy('name')->get(['id', 'name', 'employee_id']) as $user) {
                $users[] = [
                    'id' => 'sales_staff:' . $user->id,
                    'text' => trim($user->name . ($user->employee_id ? ' (' . $user->employee_id . ')' : '')) . ' — Sales Staff',
                ];
            }
        }

        if (in_array('branch_manager', $roles, true)) {
            foreach (BranchManager::orderBy('name')->get(['id', 'name', 'employee_id']) as $user) {
                $users[] = [
                    'id' => 'branch_manager:' . $user->id,
                    'text' => trim($user->name . ($user->employee_id ? ' (' . $user->employee_id . ')' : '')) . ' — Branch Manager',
                ];
            }
        }

        if (in_array('asm', $roles, true)) {
            foreach (AreaSaleManager::orderBy('name')->get(['id', 'name', 'employee_id']) as $user) {
                $users[] = [
                    'id' => 'asm:' . $user->id,
                    'text' => trim($user->name . ($user->employee_id ? ' (' . $user->employee_id . ')' : '')) . ' — ASM',
                ];
            }
        }

        return response()->json(['status' => 200, 'data' => $users]);
    }

    private function storeNotification(Request $request)
    {
        $request->validate([
            'users' => 'required|array|min:1',
            'users.*' => 'string',
        ]);

        $roleToNotifyType = [
            'sales_staff' => 'staff',
            'branch_manager' => 'manager',
            'asm' => 'asm',
        ];

        $users = [];
        foreach ($request->users as $value) {
            if (!is_string($value) || strpos($value, ':') === false) {
                continue;
            }

            [$roleKey, $id] = explode(':', $value, 2);
            $notifyType = $roleToNotifyType[$roleKey] ?? null;
            $id = (int) $id;

            if (!$notifyType || $id <= 0) {
                continue;
            }

            $modelClass = $this->resolveModelClass($notifyType);
            if (!$modelClass || !$modelClass::find($id)) {
                continue;
            }

            $users[] = ['id' => $id, 'type' => $notifyType];
        }

        if (empty($users)) {
            return back()
                ->withInput()
                ->withErrors(['users' => 'Please select at least one valid user.']);
        }

        // Prefer a single user_type when all recipients share one role; otherwise "all"
        $uniqueTypes = array_values(array_unique(array_column($users, 'type')));
        $userType = count($uniqueTypes) === 1 ? $uniqueTypes[0] : 'all';

        $adminNotification = AdminNotification::create([
            'user_type' => $userType,
            'roles' => $request->roles,
            'title' => $request->title,
            'description' => $request->description,
            'is_deleted' => 0,
        ]);

        foreach ($users as $user) {
            UserNotification::create([
                'admin_notification_id' => $adminNotification->id,
                'user_id' => $user['id'],
                'user_type' => $user['type'],
                'is_read' => 0,
            ]);

            $this->sendPushToUser(
                $user,
                $request->title,
                $request->description,
                [
                    'type' => 'notification',
                    'user_type' => (string) $userType,
                    'notification_id' => (string) $adminNotification->id,
                    'source' => 'admin_notification',
                ]
            );
        }

        return redirect()->route('announcement.index')->with('success', 'Notification Sent Successfully');
    }

    private function sendPushToUser(array $user, string $title, string $description, array $data): void
    {
        $modelClass = $this->resolveModelClass($user['type'] ?? '');
        if (!$modelClass) {
            return;
        }

        $model = $modelClass::find($user['id'] ?? 0);
        if (!$model) {
            return;
        }

        $fcmToken = $model->fcm_token ?? $model->fcm ?? null;
        if (empty($fcmToken)) {
            Log::info('Announcement push skipped: no FCM token', [
                'user_id' => $user['id'] ?? null,
                'user_type' => $user['type'] ?? null,
            ]);
            return;
        }

        try {
            NotificationHelper::sendFcmNotification($fcmToken, $title, $description, $data);
        } catch (\Exception $e) {
            Log::error('Announcement push FCM error: ' . $e->getMessage(), [
                'user_id' => $user['id'] ?? null,
                'user_type' => $user['type'] ?? null,
            ]);
        }
    }

    private function resolveModelClass(string $type): ?string
    {
        $map = [
            'staff' => SaleStaff::class,
            'manager' => BranchManager::class,
            'asm' => AreaSaleManager::class,
        ];

        return $map[$type] ?? null;
    }
}
