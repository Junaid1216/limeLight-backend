<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\JobNotification;
use App\Jobs\NotificationJob;
use App\Models\AdminNotification;
use App\Models\Notification;
use App\Models\SubAdmin;
use App\Models\User;
use App\Models\UserRolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {

        $notifications = AdminNotification::latest()->get();

        $users = User::all();

        $subadmin = SubAdmin::all();

        $sideMenuPermissions = collect();

        // ✅ Check if user is not admin (normal subadmin)

        if (! Auth::guard('admin')->check()) {

            $user = Auth::guard('subadmin')->user()->load('roles');

            // ✅ 1. Get role_id of subadmin

            $roleId = $user->role_id;

            // ✅ 2. Get all permissions assigned to this role

            $permissions = UserRolePermission::with(['permission', 'sideMenue'])

                ->where('role_id', $roleId)

                ->get();

            // ✅ 3. Group permissions by side menu

            $sideMenuPermissions = $permissions->groupBy('sideMenue.name')->map(function ($items) {

                return $items->pluck('permission.name'); // ['view', 'create']

            });

        }

        return view('admin.notification.index', compact('notifications', 'sideMenuPermissions', 'users', 'subadmin'));

    }

    
    public function store(Request $request)
    {
        // 1️⃣ Validation
        $request->validate(
            [
                'user_type' => 'required',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'users' => 'required|array', // Ensure users array provided
            ],
            [
                'user_type.required' => 'User Type is required',
            ]
        );

        // 2️⃣ Create Admin Notification (if you need a record for admin)
        AdminNotification::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        // 3️⃣ Iterate through the arrays and create notifications for each user
        foreach ($request->users as $userId) {
            // 3.1 DB Notification for User
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => $request->title,
                'description' => $request->description,
                'seenByUser' => 0, // default unseen
                'created_at' => now(),
            ]);

            // 3.2 Push Notification (if user has fcm_token)
            // $customer = User::where('id', $userId)->first();
            // dd($customer);

            // if ($customer && $customer->fcm) {
            //     // dd($customer->fcm);
            //     $data = [
            //         'id' => $notification->id,
            //         'type' => 'admin_notification', // optional type field
            //         'title' => $request->title,
            //         'body' => $request->description,
            //     ];

            //     // dd($data);

            //     // Dispatch the notification job
            //     dispatch(new JobNotification(
            //         $customer->fcm,
            //         $request->title,
            //         $request->description,
            //         $data
            //     ));
            // }
        }

        // 4️⃣ Redirect back with success after loop completes
        return redirect()->route('notification.index')
            ->with(['success' => 'Notifications Sent Successfully']);
    }

    public function destroy(Request $request, $id)
    {

        $notification = AdminNotification::find($id);
        $notification->delete();

        return redirect()->route('notification.index')->with(['success' => 'Notification Deleted Successfully']);
    }

    public function deleteAll()
    {

        AdminNotification::truncate();  // or Notification::query()->delete(); if you want model events to trigger

        return redirect()->route('notification.index')->with(['success' => 'All Notifications Have Been Deleted']);

    }

    public function getUsersByType(Request $request)
    {

        $type = $request->type;

        $users = [];

        switch ($type) {

            case 'subadmin':

                $users = SubAdmin::select('id', 'name', 'email')->get();

                break;

            case 'web':

                $users = User::select('id', 'name', 'email')->get();

                break;

        }

        return response()->json($users);

    }
}
