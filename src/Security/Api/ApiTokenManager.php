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
            $this->clock->now(),
            null !== $deviceName ? mb_substr($deviceName, 0, 255) : null,
        );
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $tokens;
    }

    /**
     * Exchanges a refresh token for a fresh pair, rotating both tokens.
     *
     * Only the current refresh token works: one that was already rotated away (replayed, or retried
     * after a lost response) is just an unknown token. There is no replay detection for now.
     *
     * @throws InvalidRefreshTokenException
     */
    public function refresh(string $refreshToken): IssuedTokens
    {
        $now = $this->clock->now();

        $session = $this->sessions->findOneByRefreshTokenHash(self::hash($refreshToken));
        if (!$session instanceof ApiSession || !$session->isRefreshTokenValid($now)) {
            throw new InvalidRefreshTokenException('Invalid or expired refresh token.');
        }

        if ($session->getUser()->isAccountLocked()) {
            throw new RefreshAccountLockedException('Account locked.');
        }

        $tokens = $this->generateTokens();
        $rotated = $this->sessions->rotateIfUnchanged(
            $session,
            $session->getRefreshTokenHash(),
            self::hash($tokens->accessToken),
            $tokens->accessTokenExpiresAt,
            self::hash($tokens->refreshToken),
            $tokens->refreshTokenExpiresAt,
            $now,
        );
        if (0 === $rotated) {
            // A concurrent refresh with the same token won the race: this one now holds a rotated-away token.
            throw new InvalidRefreshTokenException('Invalid or expired refresh token.');
        }

        $this->entityManager->refresh($session);

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

    /**
     * Revokes the session the current refresh token belongs to. Unknown tokens (including
     * ones already rotated away) are ignored so the caller can't probe for valid ones.
     */
    public function revokeByRefreshToken(string $refreshToken): void
    {
        $session = $this->sessions->findOneByRefreshTokenHash(self::hash($refreshToken));

        if ($session instanceof ApiSession && !$session->isRevoked()) {
            $this->revoke($session);
        }
    }

    /**
     * Signs the user out of every device, e.g. after a password reset.
     *
     * @return int the number of sessions that were revoked
     */
    public function revokeAllForUser(User $user): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->update(ApiSession::class, 's')
            ->set('s.revokedAt', ':now')
            ->where('s.user = :user')
            ->andWhere('s.revokedAt IS NULL')
            ->setParameter('now', $this->clock->now())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
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
