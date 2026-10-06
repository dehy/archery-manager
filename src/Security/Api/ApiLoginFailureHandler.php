<?php

declare(strict_types=1);

namespace App\Security\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * JSON answer to a failed login: 429 when throttled, the lock notice for a locked account,
 * and one single generic answer for every other cause (unknown email, wrong password...).
 */
final class ApiLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    #[\Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            $response = ApiErrorResponse::create('too_many_attempts', 'Too many login attempts. Try again later.', Response::HTTP_TOO_MANY_REQUESTS);
            $minutes = $exception->getMessageData()['%minutes%'] ?? null;
            if (is_numeric($minutes)) {
                $response->headers->set('Retry-After', (string) ((int) $minutes * 60));
            }

            return $response;
        }

        if ($exception instanceof CustomUserMessageAccountStatusException) {
            return ApiErrorResponse::create('account_locked', $exception->getMessageKey(), Response::HTTP_UNAUTHORIZED);
        }

        return ApiErrorResponse::create('invalid_credentials', 'Invalid credentials.', Response::HTTP_UNAUTHORIZED);
    }
}
