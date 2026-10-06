<?php

declare(strict_types=1);

namespace App\Security\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * JSON answer to a request to a protected API route that carries no credentials at all.
 */
final class ApiEntryPoint implements AuthenticationEntryPointInterface
{
    #[\Override]
    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        $response = ApiErrorResponse::create('authentication_required', 'Authentication required.', Response::HTTP_UNAUTHORIZED);
        $response->headers->set('WWW-Authenticate', 'Bearer');

        return $response;
    }
}
