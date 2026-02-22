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

    public const array SORTABLE_COLUMNS = [
        'equipment' => 'ce.name',
        'type' => 'ce.type',
        'borrower' => 'l.lastname',
        'quantity' => 'el.quantity',
        'startDate' => 'el.startDate',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EquipmentLoan::class);
    }

    public function findActiveLoans(?string $type = null, string $sort = 'startDate', string $dir = 'DESC'): array
    {
        $dir = 'ASC' === strtoupper($dir) ? 'ASC' : 'DESC';
        $sort = \array_key_exists($sort, self::SORTABLE_COLUMNS) ? $sort : 'startDate';

        $qb = $this->createQueryBuilder('el')
            ->join('el.equipment', 'ce')
            ->join('el.borrower', 'l')
            ->where(self::FILTER_RETURN_DATE_NULL);

        if (null !== $type && '' !== $type) {
            $qb->andWhere('ce.type = :type')
                ->setParameter('type', $type);
        }

        $qb->orderBy(self::SORTABLE_COLUMNS[$sort], $dir);

        // Secondary sort for stability
        if ('startDate' !== $sort) {
            $qb->addOrderBy('el.startDate', 'DESC');
        }

        return $qb->getQuery()->getResult();
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

    public function findActiveLoansForEquipment(ClubEquipment $equipment): array
    {
        return $this->createQueryBuilder('el')
            ->where('el.equipment = :equipment')
            ->andWhere(self::FILTER_RETURN_DATE_NULL)
            ->setParameter('equipment', $equipment)
            ->orderBy('el.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
