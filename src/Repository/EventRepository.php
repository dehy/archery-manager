<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\Season;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClass = null)
    {
        parent::__construct($registry, $entityClass ?? Event::class);
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
        int $limit = null,
    ): ArrayCollection {
        $season = Season::seasonForDate(new \DateTimeImmutable());

        $qb = $this->createQueryBuilder('e');

        return new ArrayCollection(
            $qb->select('e, ep')
                ->leftJoin('e.assignedGroups', 'g')
                ->leftJoin('e.participations', 'ep')
                ->andWhere(
                    'e.endDate >= :now',
                    $qb->expr()->orX(
                        'e.club = :club',
                        'e.club IS NULL',
                    ),
                    $qb->expr()->orX(
                        'g.club IN (:club)',
                        'g.club IS NULL',
                    ),
                    'g IN (:groups)',
                )
                ->orderBy('e.startDate', Criteria::ASC)
                ->addOrderBy('e.startTime', Criteria::ASC)
                ->addOrderBy('e.endDate', Criteria::ASC)
                ->addOrderBy('e.endTime', Criteria::ASC)
                ->groupBy('e.id')
                ->setParameter('now', new \DateTime())
                ->setParameter('groups', $licensee->getGroups())
                ->setParameter('club', $licensee->getLicenseForSeason($season)?->getClub())
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult(),
        );
    }

    /**
     * @throws \Exception
     */
    public function findForLicenseeFromDateToDate(
        Licensee $licensee,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $clubs = $licensee->getLicenses()->map(fn (License $license) => $license->getClub());
        $clubs = array_unique($clubs->toArray());

        return $this->createQueryBuilder('e')
            ->leftJoin('e.attachments', 'a')
            ->where('e.endDate >= :startDate OR e.endDate IS NULL')
            ->andWhere('e.startDate <= :endDate')
            ->andWhere('e.club IN (:clubs) OR e.club IS NULL')
            ->setParameters([
                'startDate' => $startDate,
                'endDate' => $endDate,
                'clubs' => $clubs,
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws \Exception
     */
    public function findForLicenseeSinceDate(
        Licensee $licensee,
        \DateTimeInterface $date,
    ): array {
        $clubs = $licensee->getLicenses()->map(fn (License $license) => $license->getClub());
        $clubs = array_unique($clubs->toArray());

        $qb = $this->createQueryBuilder('e');

        return $qb
            ->leftJoin('e.attachments', 'a')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        'e.recurring = TRUE',
                        'e.startDate <= :date',
                        'e.endDate >= :date OR e.endDate IS NULL',
                    ),
                    $qb->expr()->andX(
                        'e.recurring = FALSE',
                        'e.startDate >= :date',
                    )
                )
            )
            ->andWhere('e.club IN (:clubs) OR e.club IS NULL')
            ->setParameters([
                'date' => $date,
                'clubs' => $clubs,
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
