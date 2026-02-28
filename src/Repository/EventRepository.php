<?php

declare(strict_types=1);

namespace App\Repository;

use App\DBAL\Types\EventScopeType;
use App\Entity\ContestEvent;
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
    public function __construct(ManagerRegistry $registry, ?string $entityClass = null)
    {
        parent::__construct($registry, $entityClass ?? Event::class);
    }

    public function add(Event $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findNextForLicensee(
        Licensee $licensee,
        ?int $limit = null,
    ): ArrayCollection {
        $season = Season::seasonForDate(new \DateTimeImmutable());
        $club = $licensee->getLicenseForSeason($season)?->getClub();
        $departmentCode = $club?->getDepartmentCode();
        $regionCode = $club?->getRegionCode();

        $em = $this->getEntityManager();
        $qb = $this->createQueryBuilder('e');

        // DEPARTMENTAL: subquery against ContestEvent to avoid DQL field-not-found on parent
        $depCondition = '1=0';
        if ($departmentCode) {
            $depSubDql = $em->createQueryBuilder()
                ->select('ce_dep.id')
                ->from(ContestEvent::class, 'ce_dep')
                ->where('ce_dep.fftaComiteDepartemental LIKE :depPattern')
                ->getDQL();
            $depCondition = $qb->expr()->in('e.id', $depSubDql);
            $qb->setParameter('depPattern', '%'.$departmentCode.'%');
        }

        // REGIONAL: subquery against ContestEvent
        $regCondition = '1=0';
        if ($regionCode) {
            $regSubDql = $em->createQueryBuilder()
                ->select('ce_reg.id')
                ->from(ContestEvent::class, 'ce_reg')
                ->where('ce_reg.fftaComiteRegional LIKE :regPattern')
                ->getDQL();
            $regCondition = $qb->expr()->in('e.id', $regSubDql);
            $qb->setParameter('regPattern', '%'.$regionCode.'%');
        }

        $scopeExpr = $qb->expr()->orX(
            // CLUB scope: own club's events
            $qb->expr()->andX('e.scope = :scopeClub', $qb->expr()->orX('e.club = :club', 'e.club IS NULL')),
            // DEPARTMENTAL: match department code in committee name
            $qb->expr()->andX('e.scope = :scopeDep', $depCondition),
            // REGIONAL: match region code
            $qb->expr()->andX('e.scope = :scopeReg', $regCondition),
            // NATIONAL: always visible
            'e.scope = :scopeNat',
        );

        $qb->select('e, ep')
            ->leftJoin('e.assignedGroups', 'g')
            ->leftJoin('e.participations', 'ep')
            ->andWhere('e.endsAt >= :now')
            ->andWhere($scopeExpr)
            ->andWhere(
                $qb->expr()->orX(
                    // For CLUB-scope events: respect group filter
                    $qb->expr()->andX(
                        'e.scope = :scopeClub',
                        $qb->expr()->orX('g IS NULL', 'g IN (:groups)', 'g.club IS NULL'),
                    ),
                    // For broader-scope events: no group restriction
                    'e.scope != :scopeClub',
                ),
            )
            ->orderBy('e.startsAt', Criteria::ASC)
            ->addOrderBy('e.endsAt', Criteria::ASC)
            ->groupBy('e.id')
            ->setParameter('now', new \DateTime())
            ->setParameter('groups', $licensee->getGroups())
            ->setParameter('club', $club)
            ->setParameter('scopeClub', EventScopeType::CLUB)
            ->setParameter('scopeDep', EventScopeType::DEPARTMENTAL)
            ->setParameter('scopeReg', EventScopeType::REGIONAL)
            ->setParameter('scopeNat', EventScopeType::NATIONAL)
            ->setMaxResults($limit);

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    /**
     * @throws \Exception
     */
    public function findForLicenseeByMonthAndYear(Licensee $licensee, int $month, int $year): array
    {
        $clubs = $licensee->getLicenses()->map(static fn (License $license): ?\App\Entity\Club => $license->getClub());
        $clubs = array_unique($clubs->toArray());

        // Collect department and region codes for visibility filtering
        $departmentCodes = [];
        $regionCodes = [];
        foreach ($clubs as $club) {
            if ($club && $club->getDepartmentCode()) {
                $departmentCodes[] = $club->getDepartmentCode();
            }

            if ($club && $club->getRegionCode()) {
                $regionCodes[] = $club->getRegionCode();
            }
        }

        $departmentCodes = array_unique($departmentCodes);
        $regionCodes = array_unique($regionCodes);

        $firstDate = new \DateTime(\sprintf('%s-%s-01 midnight', $year, $month));
        $lastDate = (clone $firstDate)->modify('last day of this month');
        if (1 !== (int) $firstDate->format('N')) {
            $firstDate->modify('previous monday');
        }

        if (false !== $lastDate && 7 !== (int) $lastDate->format('N')) {
            $lastDate->modify('next sunday 23:59:59');
        }

        $qb = $this->createQueryBuilder('e')
            ->select('e, a')
            ->leftJoin('e.attachments', 'a')
            ->where('e.endsAt >= :monthStart')
            ->andWhere('e.startsAt <= :monthEnd')
            ->setParameter('monthStart', $firstDate)
            ->setParameter('monthEnd', $lastDate)
            ->setParameter('scopeClub', EventScopeType::CLUB)
            ->setParameter('scopeDep', EventScopeType::DEPARTMENTAL)
            ->setParameter('scopeReg', EventScopeType::REGIONAL)
            ->setParameter('scopeNat', EventScopeType::NATIONAL);

        $em = $this->getEntityManager();

        $orX = $qb->expr()->orX(
            // CLUB scope: own clubs
            $qb->expr()->andX('e.scope = :scopeClub', '(e.club IN (:clubs) OR e.club IS NULL)'),
            // NATIONAL scope: always
            'e.scope = :scopeNat',
        );

        if ([] !== $departmentCodes) {
            // Subquery against ContestEvent to avoid DQL field-not-found on parent entity
            $depSubQb = $em->createQueryBuilder()
                ->select('ce_dep2.id')
                ->from(ContestEvent::class, 'ce_dep2');
            $depOrX = $depSubQb->expr()->orX();
            foreach ($departmentCodes as $i => $depCode) {
                $paramName = 'depPattern_'.$i;
                $depOrX->add("ce_dep2.fftaComiteDepartemental LIKE :{$paramName}");
                $qb->setParameter($paramName, '%'.$depCode.'%');
            }

            $depSubQb->where($depOrX);
            $orX->add(
                $qb->expr()->andX(
                    'e.scope = :scopeDep',
                    $qb->expr()->in('e.id', $depSubQb->getDQL()),
                )
            );
        }

        if ([] !== $regionCodes) {
            // Subquery against ContestEvent to avoid DQL field-not-found on parent entity
            $regSubQb = $em->createQueryBuilder()
                ->select('ce_reg2.id')
                ->from(ContestEvent::class, 'ce_reg2');
            $regOrX = $regSubQb->expr()->orX();
            foreach ($regionCodes as $i => $regCode) {
                $paramName = 'regPattern_'.$i;
                $regOrX->add("ce_reg2.fftaComiteRegional LIKE :{$paramName}");
                $qb->setParameter($paramName, '%'.$regCode.'%');
            }

            $regSubQb->where($regOrX);
            $orX->add(
                $qb->expr()->andX(
                    'e.scope = :scopeReg',
                    $qb->expr()->in('e.id', $regSubQb->getDQL()),
                )
            );
        }

        return $qb
            ->andWhere($orX)
            ->setParameter('clubs', $clubs)
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
