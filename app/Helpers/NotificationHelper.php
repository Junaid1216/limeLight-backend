<?php
 
namespace App\Helpers;
 
use Google\Client;
use Illuminate\Support\Facades\Log;
 
class NotificationHelper
{
    private static function getGoogleAccessToken()
    {
        try {
            $client = new Client();
            $client->setAuthConfig(storage_path('app/daud-transport-firebase-adminsdk-mhl0q-18343637cd.json'));
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
                        'description' => $description,
                    ],
                    'data' => array_map('strval', $data),
                ],
            ];
 
            $url = 'https://fcm.googleapis.com/v1/projects/daud-transport/messages:send';
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
}