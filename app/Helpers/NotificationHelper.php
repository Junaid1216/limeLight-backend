<?php

namespace App\Helpers;

use App\Jobs\SendFcmPushJob;
use App\Models\AdminNotification;
use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\SaleStaff;
use App\Models\UserNotification;
use Google\Client;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    private static function firebaseCredentialsPath(): string
    {
        $preferred = storage_path('firebase/limelightsalesperformance-firebase-adminsdk-fbsvc-a3c62d666e.json');
        if (is_file($preferred)) {
            return $preferred;
        }

        return storage_path('app/limelightsalesperformance-firebase-adminsdk-fbsvc-a3c62d666e.json');
    }

    private static function getGoogleAccessToken()
    {
        try {
            $client = new Client();
            $client->setAuthConfig(self::firebaseCredentialsPath());
            $client->addScope('https://www.googleapis.com/auth/cloud-platform');
            $accessToken = $client->fetchAccessTokenWithAssertion();
            Log::info('Access Token Retrieved Successfully.');
            return $accessToken['access_token'];
        } catch (\Exception $e) {
            Log::error('Error fetching Google Access Token: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function sendFcmNotification($fcmToken, $title, $description, $data = [])
    {
        try {
            $accessToken = self::getGoogleAccessToken();
            $message = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $description,
                    ],
                    'data' => array_map('strval', $data),
                    'android' => [
                        'priority' => 'high',
                    ],
                ],
            ];

            $url = 'https://fcm.googleapis.com/v1/projects/limelightsalesperformance/messages:send';
            $headers = [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($message),
                CURLOPT_HTTPHEADER => $headers,
            ]);

            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                $error = curl_error($curl);
                Log::error('CURL Error: ' . $error);
                throw new \Exception('CURL Error: ' . $error);
            }

            $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpStatus !== 200) {
                Log::error('FCM Response Error: ' . $response);
                throw new \Exception('FCM Request failed with status ' . $httpStatus);
            }

            Log::info('FCM Response: ' . $response);
            return json_decode($response, true);
        } catch (\Exception $e) {
            Log::error('FCM Notification Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Persist in-app inbox rows, then queue FCM to every user in the given admin role keys.
     *
     * @param  array<int, string>  $roles  sales_staff | branch_manager | asm
     */
    public static function sendToRoles(array $roles, string $title, string $description, array $data = []): void
    {
        $roles = array_values(array_unique($roles));
        self::persistAndPush($title, $description, $data, self::usersFromRoles($roles), $roles);
    }

    /**
     * Persist in-app inbox rows, then queue FCM to specific users.
     *
     * @param  array<int, array{id:int|string, type:string}>  $users  type: staff | manager | asm
     */
    public static function sendToUsers(array $users, string $title, string $description, array $data = []): void
    {
        $normalized = [];
        foreach ($users as $user) {
            if (!is_array($user) || !isset($user['id'], $user['type'])) {
                continue;
            }

            $type = self::normalizeUserType((string) $user['type']);
            $id = (int) $user['id'];
            if (!$type || $id <= 0) {
                continue;
            }

            $normalized[] = ['id' => $id, 'type' => $type];
        }
        $users = $normalized;

        if (empty($users)) {
            return;
        }

        $roles = array_values(array_unique(array_filter(array_map(function ($user) {
            return self::roleFromUserType($user['type']);
        }, $users))));

        self::persistAndPush($title, $description, $data, $users, $roles);
    }

    /**
     * @param  array<int, array{id:int, type:string}>  $users
     * @param  array<int, string>  $roles
     */
    private static function persistAndPush(string $title, string $description, array $data, array $users, array $roles): void
    {
        if (empty($users)) {
            return;
        }

        $uniqueTypes = array_values(array_unique(array_column($users, 'type')));
        $userType = count($uniqueTypes) === 1 ? $uniqueTypes[0] : 'all';
        $source = (string) ($data['source'] ?? $data['type'] ?? 'system');

        $adminNotification = AdminNotification::create([
            'user_type' => $userType,
            'roles' => $roles ?: null,
            'title' => $title,
            'description' => $description,
            'is_deleted' => 0,
            'source' => $source,
        ]);

        $now = now();
        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                'admin_notification_id' => $adminNotification->id,
                'user_id' => $user['id'],
                'user_type' => $user['type'],
                'is_read' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            UserNotification::insert($chunk);
        }

        $data['notification_id'] = (string) $adminNotification->id;
        $data['user_type'] = (string) ($data['user_type'] ?? $userType);

        SendFcmPushJob::dispatch($title, $description, $data, [], $users)->onConnection('database');
    }

    /**
     * @param  array<int, string>  $roles
     * @return array<int, array{id:int, type:string}>
     */
    private static function usersFromRoles(array $roles): array
    {
        $map = [
            'sales_staff' => [SaleStaff::class, 'staff'],
            'branch_manager' => [BranchManager::class, 'manager'],
            'asm' => [AreaSaleManager::class, 'asm'],
        ];

        $users = [];
        foreach ($roles as $role) {
            if (!isset($map[$role])) {
                continue;
            }

            [$modelClass, $type] = $map[$role];
            foreach ($modelClass::query()->select('id')->cursor() as $model) {
                $users[] = [
                    'id' => (int) $model->id,
                    'type' => $type,
                ];
            }
        }

        return $users;
    }

    private static function normalizeUserType(string $type): ?string
    {
        $map = [
            'staff' => 'staff',
            'sales_staff' => 'staff',
            'sale_staff' => 'staff',
            'manager' => 'manager',
            'branch_manager' => 'manager',
            'asm' => 'asm',
            'area_sale_manager' => 'asm',
        ];

        return $map[$type] ?? null;
    }

    private static function roleFromUserType(string $type): ?string
    {
        $map = [
            'staff' => 'sales_staff',
            'manager' => 'branch_manager',
            'asm' => 'asm',
        ];

        return $map[$type] ?? null;
    }
}
