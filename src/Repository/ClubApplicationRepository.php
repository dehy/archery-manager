<?php

declare(strict_types=1);

namespace App\Repository;

use App\DBAL\Types\ClubApplicationStatusType;
use App\Entity\Club;
use App\Entity\ClubApplication;
use App\Entity\Licensee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClubApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClubApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClubApplication[]    findAll()
 * @method ClubApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<\App\Entity\ClubApplication>
 */
class ClubApplicationRepository extends ServiceEntityRepository
{
    private const string FILTER_SEASON = 'la.season = :season';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubApplication::class);
    }

    public function add(ClubApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ClubApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find pending applications for a specific club and season.
     *
     * @return ClubApplication[]
     */
    public function findPendingByClubAndSeason(Club $club, int $season): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.club = :club')
            ->andWhere(self::FILTER_SEASON)
            ->andWhere('la.status = :status')
            ->setParameter('club', $club)
            ->setParameter('season', $season)
            ->setParameter('status', ClubApplicationStatusType::PENDING)
            ->orderBy('la.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all applications for a specific club and season.
     *
     * @return ClubApplication[]
     */
    public function findByClubAndSeason(Club $club, int $season): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.club = :club')
            ->andWhere(self::FILTER_SEASON)
            ->setParameter('club', $club)
            ->setParameter('season', $season)
            ->orderBy('la.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active (pending or waiting_list) applications for a licensee in a specific season.
     *
     * @return ClubApplication[]
     */
    public function findActiveByLicensee(Licensee $licensee, int $season): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.licensee = :licensee')
            ->andWhere(self::FILTER_SEASON)
            ->andWhere('la.status IN (:statuses)')
            ->setParameter('licensee', $licensee)
            ->setParameter('season', $season)
            ->setParameter('statuses', [ClubApplicationStatusType::PENDING, ClubApplicationStatusType::WAITING_LIST])
            ->orderBy('la.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find applications for a licensee in a specific season.
     *
     * @return ClubApplication[]
     */
    public function findByLicenseeAndSeason(Licensee $licensee, int $season): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.licensee = :licensee')
            ->andWhere(self::FILTER_SEASON)
            ->setParameter('licensee', $licensee)
            ->setParameter('season', $season)
            ->orderBy('la.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
