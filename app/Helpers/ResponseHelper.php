<?php

namespace App\Helpers;

class ResponseHelper
{
     public static function success($data = '', $message = '', $status = '', $statusCode = 200) {
        $statusCode = (int) $statusCode;
        $response = [
            'status' => $status ?: 'success',
            'message' => $message,
        ];

        // Only add data if it's not null
        if (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }
    
        public static function error($data = '', $message = '', $status = '', $statusCode = null)
    {
        $statusCodeMap = [
            'error' => 400,
            'bad_request' => 400,
            'unauthorized' => 401,
            'forbidden' => 403,
            'not_found' => 404,
            'validation_error' => 422,
            'server_error' => 500,
            'already_exists' => 409
        ];

        $resolvedStatusCode = $statusCode
            ? (int) $statusCode
            : ($statusCodeMap[strtolower($status)] ?? 400);

        return response()->json([
            'status' => $status ?: 'error',
            'message' => $message,
            'data' => $data,
        ], $resolvedStatusCode);
    }
}