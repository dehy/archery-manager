<?php

declare(strict_types=1);

namespace App\Repository;

use App\DBAL\Types\EventParticipationStateType;
use App\Entity\EventParticipation;
use App\Entity\User;
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
     * Returns all REGISTERED participations for all licensees belonging to a user,
     * ordered by event start date ascending.
     *
     * @return EventParticipation[]
     */
    public function findRegisteredForUser(User $user): array
    {
        return $this->createQueryBuilder('ep')
            ->join('ep.participant', 'l')
            ->join('ep.event', 'e')
            ->where('l.user = :user')
            ->andWhere('ep.participationState = :state')
            ->setParameter('user', $user)
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
