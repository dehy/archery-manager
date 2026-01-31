<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Club;
use App\Entity\ClubEquipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubEquipment>
 */
class ClubEquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubEquipment::class);
    }

    public function findByClub(Club $club): array
    {
        return $this->createQueryBuilder('ce')
            ->where('ce.club = :club')
            ->setParameter('club', $club)
            ->orderBy('ce.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableByClub(Club $club): array
    {
        return $this->createQueryBuilder('ce')
            ->where('ce.club = :club')
            ->andWhere('ce.isAvailable = true')
            ->setParameter('club', $club)
            ->orderBy('ce.type', 'ASC')
            ->addOrderBy('ce.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentlyLoanedByClub(Club $club): array
    {
        return $this->createQueryBuilder('ce')
            ->innerJoin('ce.loans', 'l')
            ->where('ce.club = :club')
            ->andWhere('l.returnDate IS NULL')
            ->setParameter('club', $club)
            ->orderBy('ce.type', 'ASC')
            ->addOrderBy('ce.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTypeAndClub(string $type, Club $club): array
    {
        return $this->createQueryBuilder('ce')
            ->where('ce.club = :club')
            ->andWhere('ce.type = :type')
            ->setParameter('club', $club)
            ->setParameter('type', $type)
            ->orderBy('ce.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
