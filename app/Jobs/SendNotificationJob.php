<?php

namespace App\Jobs;

use App\Helpers\NotificationHelper;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\SaleStaff;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    protected array $userIds;

    public function __construct(array $data, array $userIds)
    {
        $this->data = $data;
        $this->userIds = $userIds;
    }

    public function handle(): void
    {
        Log::info('SendNotificationJob started', $this->data);

        foreach ($this->userIds as $user) {
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

            $fcmToken = $model->fcm_token ?? $model->fcm ?? null;
            if (empty($fcmToken)) {
                continue;
            }

            try {
                NotificationHelper::sendFcmNotification(
                    $fcmToken,
                    $this->data['title'],
                    $this->data['description'],
                    [
                        'user_type' => (string) ($this->data['user_type'] ?? ''),
                        'notification_id' => (string) ($this->data['notification_id'] ?? ''),
                    ]
                );
            } catch (\Exception $e) {
                Log::error('SendNotificationJob FCM error: ' . $e->getMessage(), [
                    'user_id' => $user['id'],
                    'type' => $user['type'],
                ]);
            }
        }
    }

    private function resolveModelClass(string $type): ?string
    {
        return match ($type) {
            'staff' => SaleStaff::class,
            'manager' => BranchManager::class,
            'asm' => AreaSaleManager::class,
            default => null,
        };
    }
}
