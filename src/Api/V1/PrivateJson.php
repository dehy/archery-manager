<?php

declare(strict_types=1);

namespace App\Api\V1;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Responses that depend on who is asking must never be stored by a shared cache.
 */
final class PrivateJson
{
    /**
     * @param array<string, mixed> $data
     */
    public static function response(array $data): JsonResponse
    {
        $response = new JsonResponse($data);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
