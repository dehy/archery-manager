<?php

namespace App\Repository;

use App\Entity\EventRecurringPattern;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventRecurringPattern>
 *
 * @method EventRecurringPattern|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventRecurringPattern|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventRecurringPattern[]    findAll()
 * @method EventRecurringPattern[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRecurringPatternRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventRecurringPattern::class);
    }

//    /**
//     * @return EventRecurringPattern[] Returns an array of EventRecurringPattern objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EventRecurringPattern
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
