<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        $payloadData = $data ?? (object) [];

        return response()->json([
            'ok' => true,
            'data' => $payloadData,
            'message' => $message,
        ], $status);
    }

    public static function error(string $code, string $message, mixed $details = null, int $status = 400): JsonResponse
    {
        $payloadDetails = $details ?? (object) [];

        return response()->json([
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $payloadDetails,
            ],
        ], $status);
    }
}
