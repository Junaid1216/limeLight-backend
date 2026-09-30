<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\Notification;
use App\Models\SaleStaff;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getUserNotifications(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $userType = $this->resolveNotifyUserType($user);
        if (!$userType) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized',
            ], 403);
        }

        // New admin-sent notifications
        $adminSent = UserNotification::query()
            ->with('adminNotification')
            ->where('user_id', $user->id)
            ->where('user_type', $userType)
            // ->whereHas('adminNotification', function ($q) {
            //     $q->where(function ($q2) {
            //         $q2->where('is_deleted', 0)->orWhereNull('is_deleted');
            //     });
            // })
            ->latest()
            ->get()
            ->map(function (UserNotification $row) {
                $admin = $row->adminNotification;

                return [
                    'id' => $row->id,
                    'admin_notification_id' => $row->admin_notification_id,
                    'title' => optional($admin)->title,
                    'description' => optional($admin)->description,
                    'user_type' => $row->user_type,
                    'is_read' => (bool) $row->is_read,
                    'seen' => (bool) $row->is_read,
                    'source' => 'admin_notification',
                    'created_at' => $row->created_at,
                ];
            });

        // Legacy notifications (notification_targets)
        $legacy = Notification::select('notifications.*')
            ->join('notification_targets', 'notification_targets.notification_id', '=', 'notifications.id')
            ->where('notification_targets.targetable_id', $user->id)
            ->where('notification_targets.targetable_type', get_class($user))
            ->where(function ($q) {
                $q->where('notifications.delete_by_admin', 0)
                    ->orWhereNull('notifications.delete_by_admin');
            })
            ->orderBy('notifications.created_at', 'desc')
            ->addSelect('notification_targets.seen')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'admin_notification_id' => null,
                    'title' => $row->title,
                    'description' => $row->description,
                    'user_type' => $row->user_type,
                    'is_read' => (bool) ($row->seen ?? $row->is_read ?? 0),
                    'seen' => (bool) ($row->seen ?? 0),
                    'source' => 'legacy',
                    'created_at' => $row->created_at,
                ];
            });

        $data = $adminSent
            ->concat($legacy)
            ->sortByDesc(function ($row) {
                return optional($row['created_at'])->timestamp ?? 0;
            })
            ->values();

        return response()->json([
            'status' => 200,
            'message' => 'Notifications retrieved successfully',
            'data' => $data,
        ]);
    }

    private function resolveNotifyUserType($user): ?string
    {
        if ($user instanceof SaleStaff) {
            return 'staff';
        }
        if ($user instanceof BranchManager) {
            return 'manager';
        }
        if ($user instanceof AreaSaleManager) {
            return 'asm';
        }

        return null;
    }
}
