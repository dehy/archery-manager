<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SecurityLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SecurityLog>
 */
class SecurityLogRepository extends ServiceEntityRepository
{
    private const string CONDITION_EVENT_TYPE = 'sl.eventType = :eventType';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityLog::class);
    }

    public function save(SecurityLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SecurityLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Get failed login attempts count in the last 24 hours.
     */
    public function getFailedLoginCountLast24Hours(): int
    {
        $since = new \DateTimeImmutable('-24 hours');

        return (int) $this->createQueryBuilder('sl')
            ->select('COUNT(sl.id)')
            ->where(self::CONDITION_EVENT_TYPE)
            ->andWhere('sl.occurredAt >= :since')
            ->setParameter('eventType', SecurityLog::EVENT_FAILED_LOGIN)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get currently locked accounts count.
     */
    public function getCurrentlyLockedAccountsCount(): int
    {
        return (int) $this->createQueryBuilder('sl')
            ->select('COUNT(DISTINCT sl.user)')
            ->where(self::CONDITION_EVENT_TYPE)
            ->andWhere('sl.user IS NOT NULL')
            ->setParameter('eventType', SecurityLog::EVENT_ACCOUNT_LOCKED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get most targeted accounts (top 5 by failed login attempts).
     *
     * @return array<int, array{email: string, attempts: int}>
     */
    public function getMostTargetedAccounts(int $limit = 5): array
    {
        $since = new \DateTimeImmutable('-7 days');

        return $this->createQueryBuilder('sl')
            ->select('sl.email', 'COUNT(sl.id) as attempts')
            ->where(self::CONDITION_EVENT_TYPE)
            ->andWhere('sl.occurredAt >= :since')
            ->groupBy('sl.email')
            ->orderBy('attempts', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('eventType', SecurityLog::EVENT_FAILED_LOGIN)
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get security logs for a specific user.
     *
     * @return SecurityLog[]
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('sl')
            ->where('sl.user = :user')
            ->setParameter('user', $user)
            ->orderBy('sl.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent security events.
     *
     * @return SecurityLog[]
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('sl')
            ->orderBy('sl.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
