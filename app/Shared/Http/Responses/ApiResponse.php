<?php

namespace App\Shared\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Small helper for the handful of endpoints that don't return an Eloquent
 * API Resource (e.g. login, logout, confirm/cancel actions). Standard CRUD
 * responses should prefer returning a JsonResource / ResourceCollection
 * directly — Laravel already wraps those in {"data": ...} (and adds
 * "links"/"meta" for paginated collections) without any help from here.
 */
class ApiResponse
{
    public static function success(mixed $data = null, ?array $meta = null, int $status = 200): JsonResponse
    {
        $payload = ['data' => $data];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function error(string $message, int $status = 400, ?string $errorCode = null, ?array $errors = null): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errorCode !== null) {
            $payload['error_code'] = $errorCode;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
