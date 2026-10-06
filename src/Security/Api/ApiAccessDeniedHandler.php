<?php

declare(strict_types=1);

namespace App\Security\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    #[\Override]
    public function handle(Request $request, AccessDeniedException $accessDeniedException): JsonResponse
    {
        return ApiErrorResponse::create('forbidden', 'Access denied.', Response::HTTP_FORBIDDEN);
    }
}
