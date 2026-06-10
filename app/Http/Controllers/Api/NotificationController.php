<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getUserNotifications(Request $request)
    {
        $user = Auth::user();

        return Notification::select('notifications.*')
            ->join('notification_targets', 'notification_targets.notification_id', '=', 'notifications.id')
            ->where('notification_targets.targetable_id', $user->id)
            ->where('notification_targets.targetable_type', get_class($user))
            ->orderBy('notifications.created_at', 'desc')
            ->addSelect('notification_targets.seen')
            ->get();
    }
}
