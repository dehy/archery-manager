<?php

declare(strict_types=1);

namespace App\Repository;

use App\DBAL\Types\EventParticipationStateType;
use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\Licensee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventParticipation[]    findAll()
 * @method EventParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<\App\Entity\EventParticipation>
 */
class EventParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventParticipation::class);
    }

    /**
     * How many participants of each event have not declined (everything but "not going"),
     * counted in the database: an event's `participations` collection can't be trusted when
     * the event was loaded with a grouped, fetch-joined query.
     *
     * @param list<Event> $events
     *
     * @return array<int, int> event id => attending participants
     */
    public function countAttendingByEvent(array $events): array
    {
        if ([] === $events) {
            return [];
        }

        $rows = $this->createQueryBuilder('ep')
            ->select('IDENTITY(ep.event) AS eventId', 'COUNT(ep.id) AS attending')
            ->where('ep.event IN (:events)')
            ->andWhere('ep.participationState IS NULL OR ep.participationState != :notGoing')
            ->groupBy('ep.event')
            ->setParameter('events', $events)
            ->setParameter('notGoing', EventParticipationStateType::NOT_GOING)
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['eventId']] = (int) $row['attending'];
        }

        return $counts;
    }

    /**
     * The answers a licensee gave to these events, in one query.
     *
     * @param list<Event> $events
     *
     * @return array<int, EventParticipation> event id => participation (events never answered are absent)
     */
    public function indexedByEventFor(Licensee $licensee, array $events): array
    {
        if ([] === $events) {
            return [];
        }

        $participations = $this->createQueryBuilder('ep')
            ->where('ep.participant = :licensee')
            ->andWhere('ep.event IN (:events)')
            ->setParameter('licensee', $licensee)
            ->setParameter('events', $events)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($participations as $participation) {
            $indexed[$participation->getEvent()->getId()] = $participation;
        }

        return $indexed;
    }

    /**
     * The answers given to an event, with their participants, in one query.
     *
     * @return list<EventParticipation>
     */
    public function findForEventWithParticipants(Event $event): array
    {
        return $this->createQueryBuilder('ep')
            ->select('ep', 'p')
            ->join('ep.participant', 'p')
            ->where('ep.event = :event')
            ->orderBy('ep.id', 'ASC')
            ->setParameter('event', $event)
            ->getQuery()
            ->getResult();
    }

    public function add(EventParticipation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(
        EventParticipation $entity,
        bool $flush = true,
    ): void {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Returns all REGISTERED participations for a licensee,
     * ordered by event start date ascending.
     *
     * @return EventParticipation[]
     */
    public function findRegisteredForLicensee(Licensee $licensee): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.event', 'e')
            ->where('ep.participant = :licensee')
            ->andWhere('ep.participationState = :state')
            ->setParameter('licensee', $licensee)
            ->setParameter('state', EventParticipationStateType::REGISTERED)
            ->orderBy('e.startsAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return EventParticipation[] Returns an array of EventParticipation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EventParticipation
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
