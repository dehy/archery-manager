<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ClubEquipment;
use App\Entity\EquipmentLoan;
use App\Entity\Licensee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EquipmentLoan>
 */
class EquipmentLoanRepository extends ServiceEntityRepository
{
    private const string FILTER_RETURN_DATE_NULL = 'el.returnDate IS NULL';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipmentLoan::class);
    }

    public function findActiveLoans(): array
    {
        return $this->createQueryBuilder('el')
            ->where(self::FILTER_RETURN_DATE_NULL)
            ->orderBy('el.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipment(ClubEquipment $equipment): array
    {
        return $this->createQueryBuilder('el')
            ->where('el.equipment = :equipment')
            ->setParameter('equipment', $equipment)
            ->orderBy('el.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByBorrower(Licensee $borrower): array
    {
        return $this->createQueryBuilder('el')
            ->where('el.borrower = :borrower')
            ->setParameter('borrower', $borrower)
            ->orderBy('el.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveLoansByBorrower(Licensee $borrower): array
    {
        return $this->createQueryBuilder('el')
            ->where('el.borrower = :borrower')
            ->andWhere(self::FILTER_RETURN_DATE_NULL)
            ->setParameter('borrower', $borrower)
            ->orderBy('el.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentLoanForEquipment(ClubEquipment $equipment): ?EquipmentLoan
    {
        return $this->createQueryBuilder('el')
            ->where('el.equipment = :equipment')
            ->andWhere(self::FILTER_RETURN_DATE_NULL)
            ->setParameter('equipment', $equipment)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
