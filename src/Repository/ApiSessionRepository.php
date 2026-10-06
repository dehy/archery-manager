<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApiSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiSession>
 */
class ApiSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiSession::class);
    }

    public function findOneByAccessTokenHash(string $hash): ?ApiSession
    {
        return $this->findOneBy(['accessTokenHash' => $hash]);
    }

    public function findOneByRefreshTokenHash(string $hash): ?ApiSession
    {
        return $this->findOneBy(['refreshTokenHash' => $hash]);
    }

    public function findOneByPreviousRefreshTokenHash(string $hash): ?ApiSession
    {
        return $this->findOneBy(['previousRefreshTokenHash' => $hash]);
    }

    /**
     * Rotates the tokens in a single conditional UPDATE: it only applies if the session still
     * holds the refresh token the caller read and is not revoked, so two concurrent refreshes
     * can't both win.
     *
     * @return int 1 if the session was rotated, 0 if it changed in the meantime
     */
    public function rotateIfUnchanged(
        ApiSession $session,
        string $expectedRefreshTokenHash,
        string $accessTokenHash,
        \DateTimeImmutable $accessTokenExpiresAt,
        string $refreshTokenHash,
        \DateTimeImmutable $refreshTokenExpiresAt,
        \DateTimeImmutable $now,
    ): int {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->update(ApiSession::class, 's')
            ->set('s.previousRefreshTokenHash', 's.refreshTokenHash')
            ->set('s.accessTokenHash', ':accessHash')
            ->set('s.accessTokenExpiresAt', ':accessExpires')
            ->set('s.refreshTokenHash', ':refreshHash')
            ->set('s.refreshTokenExpiresAt', ':refreshExpires')
            ->set('s.rotatedAt', ':now')
            ->set('s.lastUsedAt', ':now')
            ->where('s.id = :id')
            ->andWhere('s.refreshTokenHash = :expected')
            ->andWhere('s.revokedAt IS NULL')
            ->setParameter('accessHash', $accessTokenHash)
            ->setParameter('accessExpires', $accessTokenExpiresAt)
            ->setParameter('refreshHash', $refreshTokenHash)
            ->setParameter('refreshExpires', $refreshTokenExpiresAt)
            ->setParameter('now', $now)
            ->setParameter('id', $session->getId())
            ->setParameter('expected', $expectedRefreshTokenHash)
            ->getQuery()
            ->execute();
    }
}
