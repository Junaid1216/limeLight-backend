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

class SendFcmPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $title;
    protected string $description;
    protected array $data;
    protected array $roles;
    protected array $users;

    public function __construct(
        string $title,
        string $description,
        array $data = [],
        array $roles = [],
        array $users = []
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->data = $data;
        $this->roles = $roles;
        $this->users = $users;
        $this->onConnection('database');
    }

    public function handle(): void
    {
        Log::info('SendFcmPushJob started', [
            'title' => $this->title,
            'roles' => $this->roles,
            'users_count' => count($this->users),
            'data' => $this->data,
        ]);

        if (!empty($this->roles)) {
            $this->sendToRoles();
        }

        if (!empty($this->users)) {
            $this->sendToUsers();
        }
    }

    private function sendToRoles(): void
    {
        $map = [
            'sales_staff' => SaleStaff::class,
            'branch_manager' => BranchManager::class,
            'asm' => AreaSaleManager::class,
        ];

        foreach (array_unique($this->roles) as $role) {
            $modelClass = $map[$role] ?? null;
            if (!$modelClass) {
                continue;
            }

            foreach ($modelClass::query()->cursor() as $user) {
                $this->sendToModel($user, $role, $user->id ?? null);
            }
        }
    }

    private function sendToUsers(): void
    {
        $map = [
            'staff' => SaleStaff::class,
            'manager' => BranchManager::class,
            'asm' => AreaSaleManager::class,
        ];

        foreach ($this->users as $user) {
            if (!isset($user['id'], $user['type'])) {
                continue;
            }

            $modelClass = $map[$user['type']] ?? null;
            if (!$modelClass) {
                continue;
            }

            $model = $modelClass::find($user['id']);
            if (!$model) {
                continue;
            }

            $this->sendToModel($model, $user['type'], $user['id']);
        }
    }

    private function sendToModel($model, string $roleOrType, $userId): void
    {
        $fcmToken = $model->fcm_token ?? $model->fcm ?? null;
        if (empty($fcmToken)) {
            return;
        }

        try {
            NotificationHelper::sendFcmNotification(
                $fcmToken,
                $this->title,
                $this->description,
                $this->data
            );
        } catch (\Exception $e) {
            Log::error('SendFcmPushJob FCM error: ' . $e->getMessage(), [
                'role_or_type' => $roleOrType,
                'user_id' => $userId,
            ]);
        }
    }
}
