<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Entity\SecurityLog;
use App\Security\Api\ApiTokenManager;
use App\Security\Api\InvalidRefreshTokenException;
use App\Security\Api\RefreshTokenReuseException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AuthController
{
    public function __construct(
        private ApiTokenManager $tokenManager,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Handled by the firewall's json_login authenticator; this route only has to exist.
     */
    #[Route('/api/v1/auth/login', name: 'api_v1_auth_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Handled by the api firewall json_login authenticator.');
    }

    #[Route('/api/v1/auth/refresh', name: 'api_v1_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $this->refreshTokenFrom($request);
        if (null === $refreshToken) {
            return $this->error('invalid_request', Response::HTTP_BAD_REQUEST);
        }

        try {
            $tokens = $this->tokenManager->refresh($refreshToken);
        } catch (RefreshTokenReuseException $refreshTokenReuseException) {
            $this->logReuse($request, $refreshTokenReuseException);

            return $this->error('invalid_refresh_token', Response::HTTP_UNAUTHORIZED);
        } catch (InvalidRefreshTokenException) {
            return $this->error('invalid_refresh_token', Response::HTTP_UNAUTHORIZED);
        }

        return $this->noStore(new JsonResponse($tokens->toArray($this->clock->now())));
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

        return $this->noStore(new JsonResponse(null, Response::HTTP_NO_CONTENT));
    }

    private function refreshTokenFrom(Request $request): ?string
    {
        try {
            $refreshToken = $request->toArray()['refresh_token'] ?? null;
        } catch (\JsonException) {
            return null;
        }

        return \is_string($refreshToken) && '' !== $refreshToken ? $refreshToken : null;
    }

    private function logReuse(Request $request, RefreshTokenReuseException $exception): void
    {
        $securityLog = new SecurityLog();
        $securityLog->setUser($exception->user);
        $securityLog->setEmail((string) $exception->user->getEmail());
        $securityLog->setIpAddress($request->getClientIp() ?? 'unknown');
        $securityLog->setEventType(SecurityLog::EVENT_SUSPICIOUS_ACTIVITY);
        $securityLog->setUserAgent($request->headers->get('User-Agent', ''));
        $securityLog->setDetails('API refresh token reuse detected; session revoked');

        $this->entityManager->persist($securityLog);
        $this->entityManager->flush();
    }

    private function error(string $code, int $status): JsonResponse
    {
        return $this->noStore(new JsonResponse(['error' => $code], $status));
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
