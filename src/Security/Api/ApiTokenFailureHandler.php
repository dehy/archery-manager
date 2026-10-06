<?php

declare(strict_types=1);

namespace App\Security\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * JSON answer to a rejected bearer token (unknown, expired, revoked, or the account got locked).
 */
final class ApiTokenFailureHandler implements AuthenticationFailureHandlerInterface
{
    #[\Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $locked = $exception instanceof CustomUserMessageAccountStatusException;
        $response = ApiErrorResponse::create(
            $locked ? 'account_locked' : 'invalid_token',
            $locked ? $exception->getMessageKey() : 'Invalid or expired access token.',
            Response::HTTP_UNAUTHORIZED,
        );
        $response->headers->set('WWW-Authenticate', 'Bearer error="invalid_token"');

        return $response;
    }
}
