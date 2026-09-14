<?php

namespace App\Shared\Http;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, int $code = 200): JsonResponse
    {
        return response()->json(['data' => $data], $code);
    }

    public static function error(string $message, int $code, ?array $errors = null): JsonResponse
    {
        $payload = ['message' => $message, 'code' => $code];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
