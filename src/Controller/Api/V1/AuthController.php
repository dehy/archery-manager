<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Security\Api\ApiErrorResponse;
use App\Security\Api\ApiTokenManager;
use App\Security\Api\InvalidRefreshTokenException;
use App\Security\Api\RefreshAccountLockedException;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AuthController
{
    private const int MAX_TOKEN_LENGTH = 255;

    private const string REFRESH_FAILED_MESSAGE = 'Invalid or expired session, log in again.';

    public function __construct(
        private ApiTokenManager $tokenManager,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Credentials are checked by the firewall's json_login authenticator, which only handles
     * requests with a JSON content type. Anything else ends up here.
     */
    #[Route('/api/v1/auth/login', name: 'api_v1_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return ApiErrorResponse::create(
            'unsupported_media_type',
            'Send a JSON body with the header Content-Type: application/json.',
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
        );
    }

    #[Route('/api/v1/auth/refresh', name: 'api_v1_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $this->refreshTokenFrom($request);
        if (null === $refreshToken) {
            return ApiErrorResponse::create('invalid_request', 'The refresh_token field is required.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $tokens = $this->tokenManager->refresh($refreshToken);
        } catch (RefreshAccountLockedException) {
            return ApiErrorResponse::create('account_locked', 'Account temporarily locked.', Response::HTTP_UNAUTHORIZED);
        } catch (InvalidRefreshTokenException) {
            return ApiErrorResponse::create('invalid_refresh_token', self::REFRESH_FAILED_MESSAGE, Response::HTTP_UNAUTHORIZED);
        }

        $response = new JsonResponse($tokens->toArray($this->clock->now()));
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * Revokes the device session a refresh token belongs to.
     *
     * Identified by the refresh token rather than the access token, which has usually
     * expired by the time a user logs out. Always answers 204 so it can't be used to
     * probe for valid tokens.
     */
    #[Route('/api/v1/auth/logout', name: 'api_v1_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $this->refreshTokenFrom($request);
        if (null !== $refreshToken) {
            $this->tokenManager->revokeByRefreshToken($refreshToken);
        }

        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    private function refreshTokenFrom(Request $request): ?string
    {
        try {
            $refreshToken = $request->toArray()['refresh_token'] ?? null;
        } catch (RequestExceptionInterface) {
            // Empty or malformed body, or JSON that isn't an object: Request::toArray() throws its own exceptions.
            return null;
        }

        if (!\is_string($refreshToken) || '' === $refreshToken || \strlen($refreshToken) > self::MAX_TOKEN_LENGTH) {
            return null;
        }

        return $refreshToken;
    }
}
