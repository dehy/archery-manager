<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Club;
use App\Entity\Licensee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Licensee|null find($id, $lockMode = null, $lockVersion = null)
 * @method Licensee|null findOneBy(array $criteria, array $orderBy = null)
 * @method Licensee[]    findAll()
 * @method Licensee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LicenseeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Licensee::class);
    }

    public function add(Licensee $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(Licensee $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
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

    // /**
    //  * @return Licensee[] Returns an array of Licensee objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Licensee
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
