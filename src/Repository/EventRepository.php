<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Licensee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Event $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Event $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findNextForLicensee(
        Licensee $licensee,
        ?int $limit = null
    ): ArrayCollection {
        return new ArrayCollection(
            $this->createQueryBuilder("e")
                ->where("e IN :discipline")
                ->setParameter(
                    "discipline",
                    $licensee->getLicenseForSeason(2023)
                )
                ->getQuery()
                ->getResult()
        );
    }
}
