<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Jobs\SendNotificationJob;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Models\SaleStaff;
use App\Models\SubAdmin;
use App\Models\UserRolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('targets.targetable')
            ->where('sent_by', 'admin')
            ->where('delete_by_admin', 0)
            ->latest()
            ->get();

        $staff = SaleStaff::select('id', 'name', 'email')->get();
        $managers = BranchManager::select('id', 'name', 'email')->get();
        $asms = AreaSaleManager::select('id', 'name', 'email')->get();
        $subadmin = SubAdmin::all();

        $sideMenuPermissions = collect();

        if (!Auth::guard('admin')->check()) {
            $user = Auth::guard('subadmin')->user()->load('roles');
            $roleId = $user->role_id;
            $permissions = UserRolePermission::with(['permission', 'sideMenue'])
                ->where('role_id', $roleId)
                ->get();

            $sideMenuPermissions = $permissions->groupBy('sideMenue.name')->map(function ($items) {
                return $items->pluck('permission.name');
            });
        }

        return view('admin.notification.index', compact(
            'notifications',
            'sideMenuPermissions',
            'staff',
            'managers',
            'asms',
            'subadmin'
        ));
    }

    public function store(NotificationRequest $request)
    {
        $users = [];

        if ($request->user_type === 'staff') {
            $request->validate([
                'users.*' => 'exists:sale_staffs,id',
            ]);
            $users = array_map(fn ($id) => ['id' => $id, 'type' => 'staff'], $request->users);
        } elseif ($request->user_type === 'manager') {
            $request->validate([
                'users.*' => 'exists:branch_managers,id',
            ]);
            $users = array_map(fn ($id) => ['id' => $id, 'type' => 'manager'], $request->users);
        } elseif ($request->user_type === 'asm') {
            $request->validate([
                'users.*' => 'exists:area_sale_managers,id',
            ]);
            $users = array_map(fn ($id) => ['id' => $id, 'type' => 'asm'], $request->users);
        } elseif ($request->user_type === 'all') {
            $staffIds = SaleStaff::whereIn('id', $request->users)->pluck('id')->toArray();
            $managerIds = BranchManager::whereIn('id', $request->users)->pluck('id')->toArray();
            $asmIds = AreaSaleManager::whereIn('id', $request->users)->pluck('id')->toArray();

            if (empty($staffIds) && empty($managerIds) && empty($asmIds)) {
                return back()->withErrors(['users' => 'No Valid Staff, Manager Or ASM IDs Provided']);
            }

            $users = array_merge(
                array_map(fn ($id) => ['id' => $id, 'type' => 'staff'], $staffIds),
                array_map(fn ($id) => ['id' => $id, 'type' => 'manager'], $managerIds),
                array_map(fn ($id) => ['id' => $id, 'type' => 'asm'], $asmIds),
            );
        }

        $notification = Notification::create([
            'sent_by' => 'admin',
            'user_type' => $request->user_type,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        foreach ($users as $user) {
            if (!isset($user['id'], $user['type'])) {
                continue;
            }

            $modelClass = $this->resolveModelClass($user['type']);
            if (!$modelClass) {
                continue;
            }

            $model = $modelClass::find($user['id']);
            if (!$model) {
                continue;
            }

            NotificationTarget::create([
                'notification_id' => $notification->id,
                'targetable_id' => $model->id,
                'targetable_type' => $modelClass,
            ]);
        }

        SendNotificationJob::dispatch([
            'sent_by' => 'admin',
            'user_type' => $request->user_type,
            'title' => $request->title,
            'description' => $request->description,
            'notification_id' => $notification->id,
        ], $users);

        return redirect()->route('notification.index')->with('success', 'Notification Sent Successfully');
    }

    public function destroy(Request $request, $id)
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->delete_by_admin = 1;
            $notification->save();
        }

        return redirect()->route('notification.index')->with(['success' => 'Notification Deleted Successfully']);
    }

    public function deleteAll()
    {
        Notification::where('sent_by', 'admin')
            ->where('delete_by_admin', 0)
            ->update(['delete_by_admin' => 1]);

        return redirect()->route('notification.index')->with(['success' => 'All Notifications Have Been Deleted']);
    }

    public function getUsersByType(Request $request)
    {
        $type = $request->type;
        $users = [];

        switch ($type) {
            case 'staff':
                $users = SaleStaff::select('id', 'name', 'email')->get();
                break;
            case 'manager':
                $users = BranchManager::select('id', 'name', 'email')->get();
                break;
            case 'asm':
                $users = AreaSaleManager::select('id', 'name', 'email')->get();
                break;
            case 'subadmin':
                $users = SubAdmin::select('id', 'name', 'email')->get();
                break;
        }

        return response()->json($users);
    }

    public function read($id)
    {
        $notification = Notification::findOrFail($id);

        $notification->update([
            'is_read' => 1,
        ]);

        if ($notification->type == 'monthly_target') {
            return redirect()->route('admin.target.approvals');
        }

        return back();
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
