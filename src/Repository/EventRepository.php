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
                    'e.endsAt >= :now',
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
                ->orderBy('e.startsAt', Criteria::ASC)
                ->addOrderBy('e.endsAt', Criteria::ASC)
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
    public function findForLicenseeByMonthAndYear(Licensee $licensee, int $month, int $year): array
    {
        $clubs = $licensee->getLicenses()->map(fn (License $license) => $license->getClub());
        $clubs = \array_unique($clubs->toArray());

        $firstDate = new \DateTime(sprintf('%s-%s-01 midnight', $year, $month));
        $lastDate = (clone $firstDate)->modify('last day of this month');
        if (1 !== (int) $firstDate->format('N')) {
            $firstDate->modify('previous monday');
        }
        if (false !== $lastDate && 7 !== (int) $lastDate->format('N')) {
            $lastDate->modify('next sunday 23:59:59');
        }

        return $this->createQueryBuilder('e')
            ->select('e, a')
            ->leftJoin('e.attachments', 'a')
            ->where('e.endsAt >= :monthStart')
            ->andWhere('e.startsAt <= :monthEnd')
            ->andWhere('e.club IN (:clubs) OR e.club IS NULL')
            ->setParameters([
                'monthStart' => $firstDate,
                'monthEnd' => $lastDate,
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
