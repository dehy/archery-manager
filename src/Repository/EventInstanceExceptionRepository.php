<?php

namespace App\Repository;

use App\Entity\EventInstanceException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventInstanceException>
 *
 * @method EventInstanceException|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventInstanceException|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventInstanceException[]    findAll()
 * @method EventInstanceException[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventInstanceExceptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventInstanceException::class);
    }

//    /**
//     * @return EventInstanceException[] Returns an array of EventInstanceException objects
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

//    public function findOneBySomeField($value): ?EventInstanceException
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
