<?php

declare(strict_types=1);

namespace App\Repository;

use App\DBAL\Types\LicenseApplicationStatusType;
use App\Entity\Club;
use App\Entity\LicenseApplication;
use App\Entity\Licensee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LicenseApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method LicenseApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method LicenseApplication[]    findAll()
 * @method LicenseApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<\App\Entity\LicenseApplication>
 */
class LicenseApplicationRepository extends ServiceEntityRepository
{
    private const string FILTER_SEASON = 'la.season = :season';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LicenseApplication::class);
    }

    public function add(LicenseApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LicenseApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find pending applications for a specific club and season.
     *
     * @return LicenseApplication[]
     */
    public function findPendingByClubAndSeason(Club $club, int $season): array
    {
        return $this->createQueryBuilder('la')
            ->andWhere('la.club = :club')
            ->andWhere(self::FILTER_SEASON)
            ->andWhere('la.status = :status')
            ->setParameter('club', $club)
            ->setParameter('season', $season)
            ->setParameter('status', LicenseApplicationStatusType::PENDING)
            ->orderBy('la.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all applications for a specific club and season.
     *
     * @return LicenseApplication[]
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
     * Find applications for a licensee in a specific season.
     *
     * @return LicenseApplication[]
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
