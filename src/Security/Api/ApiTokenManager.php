<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Entity\ApiSession;
use App\Entity\User;
use App\Repository\ApiSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Issues, rotates and revokes the opaque tokens of mobile API sessions.
 */
class ApiTokenManager
{
    final public const int ACCESS_TOKEN_TTL_SECONDS = 900;

    final public const int REFRESH_TOKEN_TTL_SECONDS = 2592000;

    private const int TOKEN_BYTES = 32;

    private const int LAST_USED_GRANULARITY_SECONDS = 60;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiSessionRepository $sessions,
        private readonly ClockInterface $clock,
    ) {
    }

    public function issue(User $user, ?string $deviceName = null): IssuedTokens
    {
        $tokens = $this->generateTokens();

        $session = new ApiSession(
            $user,
            self::hash($tokens->accessToken),
            $tokens->accessTokenExpiresAt,
            self::hash($tokens->refreshToken),
            $tokens->refreshTokenExpiresAt,
            null !== $deviceName ? mb_substr($deviceName, 0, 255) : null,
        );
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $tokens;
    }

    /**
     * Exchanges a refresh token for a fresh pair, rotating both tokens.
     *
     * Presenting an already-rotated refresh token means it leaked: the whole
     * session is revoked.
     *
     * @throws InvalidRefreshTokenException
     */
    public function refresh(string $refreshToken): IssuedTokens
    {
        $now = $this->clock->now();
        $hash = self::hash($refreshToken);

        $replayed = $this->sessions->findOneByPreviousRefreshTokenHash($hash);
        if ($replayed instanceof ApiSession) {
            $this->revoke($replayed);

            throw new RefreshTokenReuseException($replayed->getUser());
        }

        $session = $this->sessions->findOneByRefreshTokenHash($hash);
        if (!$session instanceof ApiSession || !$session->isRefreshTokenValid($now)) {
            throw new InvalidRefreshTokenException('Invalid or expired refresh token.');
        }

        if ($session->getUser()->isAccountLocked()) {
            $this->revoke($session);

            throw new InvalidRefreshTokenException('Account locked.');
        }

        $tokens = $this->generateTokens();
        $session->rotate(
            self::hash($tokens->accessToken),
            $tokens->accessTokenExpiresAt,
            self::hash($tokens->refreshToken),
            $tokens->refreshTokenExpiresAt,
        );
        $session->touch($now);

        $this->entityManager->flush();

        return $tokens;
    }

    public function findValidSessionByAccessToken(string $accessToken): ?ApiSession
    {
        $session = $this->sessions->findOneByAccessTokenHash(self::hash($accessToken));
        if (!$session instanceof ApiSession) {
            return null;
        }

        $now = $this->clock->now();
        if (!$session->isAccessTokenValid($now)) {
            return null;
        }

        $lastUsed = $session->getLastUsedAt();
        if (!$lastUsed instanceof \DateTimeImmutable
            || $now->getTimestamp() - $lastUsed->getTimestamp() >= self::LAST_USED_GRANULARITY_SECONDS) {
            $session->touch($now);
            $this->entityManager->flush();
        }

        return $session;
    }

    public function revoke(ApiSession $session): void
    {
        $session->revoke($this->clock->now());
        $this->entityManager->flush();
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function generateTokens(): IssuedTokens
    {
        $now = $this->clock->now();

        return new IssuedTokens(
            bin2hex(random_bytes(self::TOKEN_BYTES)),
            $now->modify(\sprintf('+%d seconds', self::ACCESS_TOKEN_TTL_SECONDS)),
            bin2hex(random_bytes(self::TOKEN_BYTES)),
            $now->modify(\sprintf('+%d seconds', self::REFRESH_TOKEN_TTL_SECONDS)),
        );
    }
}
