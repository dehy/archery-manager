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
}
