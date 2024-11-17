<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SightAdjustment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SightAdjustment|null find($id, $lockMode = null, $lockVersion = null)
 * @method SightAdjustment|null findOneBy(array $criteria, array $orderBy = null)
 * @method SightAdjustment[]    findAll()
 * @method SightAdjustment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SightAdjustmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SightAdjustment::class);
    }

    public function add(SightAdjustment $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(SightAdjustment $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return SightAdjustment[] Returns an array of SightAdjustment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SightAdjustment
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
