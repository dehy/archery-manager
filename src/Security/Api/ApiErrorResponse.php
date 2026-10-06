<?php

declare(strict_types=1);

namespace App\Security\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The single error shape of the mobile API: a stable machine-readable `error` code
 * plus a human-readable `message`.
 */
final class ApiErrorResponse
{
    public static function create(string $error, string $message, int $status): JsonResponse
    {
        $response = new JsonResponse(['error' => $error, 'message' => $message], $status);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
