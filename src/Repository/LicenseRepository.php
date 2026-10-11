<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\License;
use App\Entity\Licensee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method License|null find($id, $lockMode = null, $lockVersion = null)
 * @method License|null findOneBy(array $criteria, array $orderBy = null)
 * @method License[]    findAll()
 * @method License[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<\App\Entity\License>
 */
class LicenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, License::class);
    }

    /**
     * Among $targets, the licensees holding a license for $season in a club where one of $viewers also holds
     * one for that season: the "shares a club" rule of LicenseeAccessVoter, for a whole list in one query.
     *
     * @param list<Licensee> $targets
     * @param list<Licensee> $viewers
     *
     * @return list<int> licensee ids
     */
    public function idsSharingAClubWith(array $targets, array $viewers, int $season): array
    {
        if ([] === $targets || [] === $viewers) {
            return [];
        }

        $viewerClubs = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.club)')
            ->where('v.season = :season')
            ->andWhere('v.licensee IN (:viewers)')
            ->andWhere('v.club IS NOT NULL');

        $rows = $this->createQueryBuilder('t')
            ->select('DISTINCT IDENTITY(t.licensee) AS licenseeId')
            ->where('t.season = :season')
            ->andWhere('t.licensee IN (:targets)')
            ->andWhere(\sprintf('t.club IN (%s)', $viewerClubs->getDQL()))
            ->setParameter('season', $season)
            ->setParameter('targets', $targets)
            ->setParameter('viewers', $viewers)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map(intval(...), $rows);
    }

    public function add(License $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(License $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // /**
    //  * @return License[] Returns an array of License objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?License
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
