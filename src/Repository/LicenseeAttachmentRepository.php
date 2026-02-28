<?php

declare(strict_types=1);

namespace App\Repository;

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
}
