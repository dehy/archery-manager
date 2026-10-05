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
     * Licensees of a club for a season, with their account and groups, for the member management page.
     *
     * @return list<Licensee>
     */
    public function findForMemberManagement(Club $club, int $season, ?string $search = null, ?Group $group = null, bool $sharedAccountOnly = false): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('l, li, u, g')
            ->innerJoin('l.licenses', 'li')
            ->innerJoin('l.user', 'u')
            ->leftJoin('l.groups', 'g')
            ->where('li.season = :season')
            ->andWhere('li.club = :club')
            ->setParameter('season', $season)
            ->setParameter('club', $club)
            ->orderBy('l.lastname', 'ASC')
            ->addOrderBy('l.firstname', 'ASC');

        $search = null === $search ? '' : trim($search);
        if ('' !== $search) {
            $qb->andWhere('l.firstname LIKE :search OR l.lastname LIKE :search OR l.fftaMemberCode LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%'.addcslashes($search, '%_\\').'%');
        }

        if ($group instanceof Group) {
            $qb->andWhere(':group MEMBER OF l.groups')
                ->setParameter('group', $group);
        }

        if ($sharedAccountOnly) {
            $qb->andWhere('u.id IN (SELECT IDENTITY(l2.user) FROM '.Licensee::class.' l2 GROUP BY l2.user HAVING COUNT(l2.id) > 1)');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Number of licensees attached to each of the given accounts, whatever their club or season.
     *
     * @param list<int> $userIds
     *
     * @return array<int, int> user id => licensee count
     */
    public function countByUserIds(array $userIds): array
    {
        if ([] === $userIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('l')
            ->select('IDENTITY(l.user) AS userId, COUNT(l.id) AS total')
            ->where('l.user IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->groupBy('l.user')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['userId']] = (int) $row['total'];
        }

        return $counts;
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
