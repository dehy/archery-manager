<?php

declare(strict_types=1);

namespace App\Repository;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LicenseeAttachment>
 *
 * @method LicenseeAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method LicenseeAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method LicenseeAttachment[]    findAll()
 * @method LicenseeAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LicenseeAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LicenseeAttachment::class);
    }

    /**
     * Which of these licensees have a profile picture, in a single query.
     *
     * @param list<Licensee> $licensees
     *
     * @return array<int, true> licensee id => true
     */
    public function profilePictureOwners(array $licensees): array
    {
        if ([] === $licensees) {
            return [];
        }

        $ids = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.licensee)')
            ->where('a.type = :type')
            ->andWhere('a.licensee IN (:licensees)')
            ->setParameter('type', LicenseeAttachmentType::PROFILE_PICTURE)
            ->setParameter('licensees', $licensees)
            ->getQuery()
            ->getSingleColumnResult();

        return array_fill_keys(array_map(intval(...), $ids), true);
    }

    public function add(LicenseeAttachment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LicenseeAttachment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Returns LicenseeAttachment records of type MEDICAL_CERTIFICATE whose documentDate
     * will be invalid at the next season renewal and that have not yet received a reminder
     * during the current campaign window (June–August).
     *
     * @return LicenseeAttachment[]
     */
    public function findNeedingCaciReminder(
        \DateTimeImmutable $threshold,
        \DateTimeImmutable $campaignStart,
        \DateTimeImmutable $campaignEnd,
    ): array {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.licensee', 'l')
            ->addSelect('l')
            ->where('a.type = :type')
            ->andWhere('a.documentDate IS NOT NULL')
            ->andWhere('a.documentDate < :threshold')
            ->andWhere(
                'a.lastCaciReminderSentAt IS NULL
                 OR a.lastCaciReminderSentAt < :campaignStart
                 OR a.lastCaciReminderSentAt > :campaignEnd'
            )
            ->setParameter('type', LicenseeAttachmentType::MEDICAL_CERTIFICATE)
            ->setParameter('threshold', $threshold)
            ->setParameter('campaignStart', $campaignStart)
            ->setParameter('campaignEnd', $campaignEnd)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return LicenseeAttachment[] Returns an array of LicenseeAttachment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?LicenseeAttachment
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
