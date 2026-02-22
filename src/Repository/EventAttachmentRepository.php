<?php

declare(strict_types=1);

namespace App\Repository;

use App\DBAL\Types\EventAttachmentType;
use App\Entity\Event;
use App\Entity\EventAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventAttachment>
 *
 * @method EventAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventAttachment[]    findAll()
 * @method EventAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventAttachment::class);
    }

    public function add(EventAttachment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EventAttachment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAttachmentForEvent(Event $event, string $attachmentType): ?EventAttachment
    {
        EventAttachmentType::assertValidChoice($attachmentType);

        return $this->createQueryBuilder('ea')
            ->where('ea.event = :event')
            ->andWhere('ea.type = :type')
            ->setParameter('event', $event)
            ->setParameter('type', $attachmentType)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
