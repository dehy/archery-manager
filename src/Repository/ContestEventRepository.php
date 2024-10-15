<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContestEvent;
use App\Entity\Event;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContestEventRepository extends EventRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContestEvent::class);
    }

    #[\Override]
    public function add(Event $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    #[\Override]
    public function remove(Event $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
