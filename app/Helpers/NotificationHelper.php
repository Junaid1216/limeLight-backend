<?php

namespace App\Helpers;

use App\Models\AreaSaleManager;
use App\Models\BranchManager;
use App\Models\SaleStaff;
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
     * Send FCM to every user in the given admin role keys.
     *
     * @param  array<int, string>  $roles  sales_staff | branch_manager | asm
     */
    public static function sendToRoles(array $roles, string $title, string $description, array $data = []): void
    {
        $map = [
            'sales_staff' => SaleStaff::class,
            'branch_manager' => BranchManager::class,
            'asm' => AreaSaleManager::class,
        ];

        foreach (array_unique($roles) as $role) {
            $modelClass = $map[$role] ?? null;
            if (!$modelClass) {
                continue;
            }

            foreach ($modelClass::query()->cursor() as $user) {
                $fcmToken = $user->fcm_token ?? $user->fcm ?? null;
                if (empty($fcmToken)) {
                    continue;
                }

                try {
                    self::sendFcmNotification($fcmToken, $title, $description, $data);
                } catch (\Exception $e) {
                    Log::error('Role push FCM error: ' . $e->getMessage(), [
                        'role' => $role,
                        'user_id' => $user->id ?? null,
                    ]);
                }
            }
        }
    }
}
