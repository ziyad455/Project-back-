<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param  array<string, mixed>  $data
     */
    public static function success(array $data = [], string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error JSON response.
     *
     * @param  array<string, mixed>  $data
     */
    public static function error(string $message = 'Error', int $status = 400, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
