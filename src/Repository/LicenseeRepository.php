<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Club;
use App\Entity\Group;
use App\Entity\Licensee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Licensee|null find($id, $lockMode = null, $lockVersion = null)
 * @method Licensee|null findOneBy(array $criteria, array $orderBy = null)
 * @method Licensee[]    findAll()
 * @method Licensee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository<\App\Entity\Licensee>
 */
class LicenseeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Licensee::class);
    }

    public function add(Licensee $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Licensee $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByCode(string $fftaCode): ?Licensee
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.fftaMemberCode = :fftaCode')
            ->setParameter('fftaCode', $fftaCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<string> $fftaCodes
     *
     * @return list<Licensee>
     */
    public function findByCodesWithLicenses(array $fftaCodes): array
    {
        if ([] === $fftaCodes) {
            return [];
        }

        return $this->createQueryBuilder('l')
            ->addSelect('licenses')
            ->leftJoin('l.licenses', 'licenses')
            ->where('l.fftaMemberCode IN (:fftaCodes)')
            ->setParameter('fftaCodes', array_values(array_unique($fftaCodes)))
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByFftaId(int $fftaId): ?Licensee
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.fftaId = :fftaId')
            ->setParameter('fftaId', $fftaId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByLicenseYear(Club $club, int $year): array
    {
        return $this->createQueryBuilder('l')
            ->select('l, li, a, g')
            ->leftJoin('l.licenses', 'li')
            ->leftJoin('l.attachments', 'a')
            ->leftJoin('l.groups', 'g')
            ->where('li.season = :year')
            ->andWhere('li.club = :club')
            ->setParameter('year', $year)
            ->setParameter('club', $club)
            ->getQuery()
            ->getResult();
    }

    /**
     * The members of a club for a season, with what the member directory needs and nothing more
     * (no attachments, which can be many and heavy), in a stable order.
     *
     * Careful: licenses are fetch-joined for the requested season only, so the `licenses` collection of
     * the returned licensees must not be relied upon for another season within the same request.
     *
     * @return list<Licensee>
     */
    public function findForDirectory(Club $club, int $season): array
    {
        return $this->createQueryBuilder('l')
            ->select('l', 'li', 'g')
            ->innerJoin('l.licenses', 'li')
            ->leftJoin('l.groups', 'g')
            ->where('li.season = :season')
            ->andWhere('li.club = :club')
            ->orderBy('l.id', 'ASC')
            ->setParameter('season', $season)
            ->setParameter('club', $club)
            ->getQuery()
            ->getResult();
    }

    /**
     * The licensees of these groups who hold a license for the season (in $club, when given): the people
     * a group-assigned event is for.
     *
     * @param list<Group> $groups
     *
     * @return list<Licensee>
     */
    public function findInGroupsForSeason(array $groups, int $season, ?Club $club): array
    {
        if ([] === $groups) {
            return [];
        }

        $qb = $this->createQueryBuilder('l')
            ->select('DISTINCT l')
            ->innerJoin('l.groups', 'g')
            ->innerJoin('l.licenses', 'li')
            ->where('g IN (:groups)')
            ->andWhere('li.season = :season')
            ->orderBy('l.id', 'ASC')
            ->setParameter('groups', $groups)
            ->setParameter('season', $season);
        if ($club instanceof Club) {
            $qb->andWhere('li.club = :club')->setParameter('club', $club);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByCalendarToken(string $token): ?Licensee
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.calendarToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
