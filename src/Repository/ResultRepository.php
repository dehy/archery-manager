<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContestEvent;
use App\Entity\Licensee;
use App\Entity\Result;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Result|null find($id, $lockMode = null, $lockVersion = null)
 * @method Result|null findOneBy(array $criteria, array $orderBy = null)
 * @method Result[]    findAll()
 * @method Result[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<\App\Entity\Result>
 */
class ResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Result::class);
    }

    public function add(Result $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Result $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLastForLicensee(Licensee $licensee, int $count = 5): ?array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.licensee = :licensee')
            ->orderBy('e.endsAt', Criteria::DESC)
            ->setMaxResults($count)
            ->setParameter('licensee', $licensee)
            ->getQuery()
            ->getResult();
    }

    /**
     * The results of a contest with their archers, in one query.
     *
     * @return list<Result>
     */
    public function findForEventWithLicensees(ContestEvent $event): array
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'l')
            ->join('r.licensee', 'l')
            ->where('r.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getResult();
    }

    /**
     * A licensee's results over all seasons, newest contest first, with their contests in one query.
     *
     * @return list<Result>
     */
    public function findHistoryForLicensee(Licensee $licensee): array
    {
        return $this->createQueryBuilder('r')
            ->select('r', 'e')
            ->join('r.event', 'e')
            ->where('r.licensee = :licensee')
            ->orderBy('e.startsAt', Criteria::DESC)
            ->addOrderBy('r.id', Criteria::DESC)
            ->setParameter('licensee', $licensee)
            ->getQuery()
            ->getResult();
    }

    public function findForLicensee(Licensee $licensee): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.licensee = :licensee')
            ->orderBy('e.startsAt', Criteria::ASC)
            ->setParameter('licensee', $licensee)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Result[] Returns an array of Result objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Result
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
