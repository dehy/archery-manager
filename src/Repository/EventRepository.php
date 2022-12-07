<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Licensee;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @method null|Event find($id, $lockMode = null, $lockVersion = null)
 * @method null|Event findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function add(Event $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(Event $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findNextForLicensee(
        Licensee $licensee,
        ?int $limit = null,
    ): ArrayCollection {
        return new ArrayCollection(
            $this->createQueryBuilder('e')
                ->leftJoin('e.assignedGroups', 'g')
                ->where('e.endsAt >= :now')
                ->andWhere('g IN (:groups)')
                ->orderBy('e.startsAt', 'ASC')
                ->addOrderBy('e.endsAt', 'ASC')
                ->setParameter('now', new DateTime())
                ->setParameter('groups', $licensee->getGroups())
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult(),
        );
    }

    /**
     * @throws Exception
     */
    public function findForMonthAndYear(int $month, int $year): array
    {
        $firstDate = new DateTime(sprintf('%s-%s-01 midnight', $year, $month));
        $lastDate = (clone $firstDate)->modify('last day of this month');
        if (1 !== (int) $firstDate->format('N')) {
            $firstDate->modify('previous monday');
        }
        if (false !== $lastDate && 7 !== (int) $lastDate->format('N')) {
            $lastDate->modify('next sunday 23:59:59');
        }

        return $this->createQueryBuilder('e')
            ->where('e.endsAt >= :monthStart')
            ->andWhere('e.startsAt <= :monthEnd')
            ->setParameters([
                'monthStart' => $firstDate,
                'monthEnd' => $lastDate,
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findBySlug(string $slug): ?Event
    {
        return $this->createQueryBuilder('e')
            ->where('e.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
