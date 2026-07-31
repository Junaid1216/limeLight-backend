<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use App\Jobs\SendNotificationJob;

class DispatchNotificationJobs extends Command
{
    protected $signature = 'notifications:dispatch';

    protected $description = 'Dispatch jobs to send unsent notifications';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $unsentNotifications = Notification::with('targets')
            ->where('is_sent', false)
            ->where('delete_by_admin', 0)
            ->get();

        foreach ($unsentNotifications as $notification) {
            $users = $notification->targets->map(function ($target) {
                $type = null;
                if ($target->targetable_type === \App\Models\SaleStaff::class) {
                    $type = 'staff';
                } elseif ($target->targetable_type === \App\Models\BranchManager::class) {
                    $type = 'manager';
                } elseif ($target->targetable_type === \App\Models\AreaSaleManager::class) {
                    $type = 'asm';
                }

                if (!$type) {
                    return null;
                }

                return [
                    'id' => $target->targetable_id,
                    'type' => $type,
                ];
            })->filter()->values()->toArray();

            if (empty($users)) {
                continue;
            }

            SendNotificationJob::dispatch([
                'sent_by' => $notification->sent_by ?? 'admin',
                'user_type' => $notification->user_type,
                'title' => $notification->title,
                'description' => $notification->description,
                'notification_id' => $notification->id,
            ], $users);

            $notification->update(['is_sent' => true]);
        }

        $this->info('Jobs dispatched for unsent notifications.');
    }
}
