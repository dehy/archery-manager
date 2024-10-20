<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Licensee;
use App\Entity\PracticeAdvice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticeAdvice>
 *
 * @method PracticeAdvice|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticeAdvice|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticeAdvice[]    findAll()
 * @method PracticeAdvice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticeAdviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticeAdvice::class);
    }

    public function add(PracticeAdvice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticeAdvice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findForLicensee(Licensee $licensee): array
    {
        return $this->createQueryBuilder('pa')
            ->select('pa')
            ->join('pa.licensee', 'l')
            ->where('l = :licensee')
            ->setParameter('licensee', $licensee)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return PracticeAdvice[] Returns an array of PracticeAdvice objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PracticeAdvice
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
