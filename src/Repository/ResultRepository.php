<?php

namespace App\Repository;

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
 */
class ResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Result::class);
    }

    public function add(Result $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(Result $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findLastForLicensee(Licensee $licensee, int $count = 5): ?array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.licensee = :licensee')
            ->orderBy('e.endDate', Criteria::DESC)
            ->orderBy('e.endTime', Criteria::DESC)
            ->setMaxResults($count)
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
