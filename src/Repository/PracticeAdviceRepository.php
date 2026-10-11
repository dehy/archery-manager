<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Licensee;
use App\Entity\PracticeAdvice;
use App\Entity\PracticeAdviceAttachment;
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

    /**
     * The advice given to a licensee, newest first (archived advice only when asked for), with its author.
     *
     * @return list<PracticeAdvice>
     */
    public function findForLicenseeNewestFirst(Licensee $licensee, bool $includeArchived): array
    {
        $qb = $this->createQueryBuilder('pa')
            ->select('pa', 'a')
            ->join('pa.author', 'a')
            ->where('pa.licensee = :licensee')
            ->orderBy('pa.createdAt', 'DESC')
            ->addOrderBy('pa.id', 'DESC')
            ->setParameter('licensee', $licensee);
        if (!$includeArchived) {
            $qb->andWhere('pa.archivedAt IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param list<PracticeAdvice> $advices
     *
     * @return array<int, int> advice id => number of attachments
     */
    public function countAttachments(array $advices): array
    {
        if ([] === $advices) {
            return [];
        }

        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(at.practiceAdvice) AS adviceId', 'COUNT(at.id) AS total')
            ->from(PracticeAdviceAttachment::class, 'at')
            ->where('at.practiceAdvice IN (:advices)')
            ->groupBy('at.practiceAdvice')
            ->setParameter('advices', $advices)
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['adviceId']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return list<PracticeAdviceAttachment>
     */
    public function attachmentsOf(PracticeAdvice $advice): array
    {
        // Not getRepository(): PracticeAdviceAttachment is mapped with EventAttachmentRepository.
        return $this->getEntityManager()->createQueryBuilder()
            ->select('at')
            ->from(PracticeAdviceAttachment::class, 'at')
            ->where('at.practiceAdvice = :advice')
            ->orderBy('at.id', 'ASC')
            ->setParameter('advice', $advice)
            ->getQuery()
            ->getResult();
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
