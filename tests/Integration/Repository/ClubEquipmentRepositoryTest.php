<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\DBAL\Types\ClubEquipmentType;
use App\Entity\Club;
use App\Entity\ClubEquipment;
use App\Entity\EquipmentLoan;
use App\Entity\Licensee;
use App\Repository\ClubEquipmentRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ClubEquipmentRepositoryTest extends KernelTestCase
{
    private const string CLUB_NAME_LADG = 'Les Archers de Guyenne';
    private const string CLUB_NAME_LADB = 'Les Archers du Bosquet';

    private ?EntityManager $entityManager;
    private ?ClubEquipmentRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(ClubEquipment::class);
    }

    public function testFindByClubReturnsEquipmentOrderedByCreatedAt(): void
    {
        $club = $this->entityManager->getRepository(Club::class)
            ->findOneBy(['name' => self::CLUB_NAME_LADG]);
        $this->assertInstanceOf(Club::class, $club);

        $equipment = $this->repository->findByClub($club);

        $this->assertIsArray($equipment);
        // Verify ordering: most recent first
        if (count($equipment) > 1) {
            $this->assertGreaterThanOrEqual(
                $equipment[1]->getCreatedAt(),
                $equipment[0]->getCreatedAt()
            );
        }
    }

    public function testFindByClubReturnsEmptyArrayForClubWithNoEquipment(): void
    {
        $club = $this->entityManager->getRepository(Club::class)
            ->findOneBy(['name' => self::CLUB_NAME_LADB]);
        $this->assertInstanceOf(Club::class, $club);

        $equipment = $this->repository->findByClub($club);

        $this->assertIsArray($equipment);
        $this->assertCount(0, $equipment);
    }

    public function testFindAvailableByClubReturnsOnlyAvailableEquipment(): void
    {
        $club = $this->entityManager->getRepository(Club::class)
            ->findOneBy(['name' => self::CLUB_NAME_LADG]);
        $this->assertInstanceOf(Club::class, $club);

        // Create test equipment with different availability
        $available = new ClubEquipment();
        $available->setClub($club);
        $available->setType(ClubEquipmentType::BOW);
        $available->setName('Available Bow');
        $available->setIsAvailable(true);

        $unavailable = new ClubEquipment();
        $unavailable->setClub($club);
        $unavailable->setType(ClubEquipmentType::BOW);
        $unavailable->setName('Unavailable Bow');
        $unavailable->setIsAvailable(false);

        $this->entityManager->persist($available);
        $this->entityManager->persist($unavailable);
        $this->entityManager->flush();

        $equipment = $this->repository->findAvailableByClub($club);

        $this->assertIsArray($equipment);
        foreach ($equipment as $item) {
            $this->assertTrue($item->isAvailable());
        }
        $this->assertContains($available, $equipment);
        $this->assertNotContains($unavailable, $equipment);
    }

    public function testFindAvailableByClubOrdersByTypeAndName(): void
    {
        $club = $this->entityManager->getRepository(Club::class)
            ->findOneBy(['name' => self::CLUB_NAME_LADG]);
        $this->assertInstanceOf(Club::class, $club);

        $equipment = $this->repository->findAvailableByClub($club);

        $this->assertIsArray($equipment);
        // Verify ordering: type ASC, name ASC
        if (count($equipment) > 1) {
            for ($i = 0; $i < count($equipment) - 1; $i++) {
                $current = $equipment[$i];
                $next = $equipment[$i + 1];
                
                if ($current->getType() === $next->getType()) {
                    $this->assertLessThanOrEqual(
                        $next->getName(),
                        $current->getName()
                    );
                }
            }
        }
    }

    public function testFindCurrentlyLoanedByClubReturnsOnlyLoanedEquipment(): void
    {
        $club = $this->entityManager->getRepository(Club::class)
            ->findOneBy(['name' => self::CLUB_NAME_LADG]);
        $this->assertInstanceOf(Club::class, $club);

        $licensees = $this->entityManager->getRepository(Licensee::class)->findAll();
        $this->assertNotEmpty($licensees);
        $licensee = $licensees[0];

        // Create loaned equipment
        $loanedEquipment = new ClubEquipment();
        $loanedEquipment->setClub($club);
        $loanedEquipment->setType(ClubEquipmentType::ARROWS);
        $loanedEquipment->setName('Loaned Arrows');

        $loan = new EquipmentLoan();
        $loan->setEquipment($loanedEquipment);
        $loan->setBorrower($licensee);
        $loan->setStartDate(new \DateTimeImmutable());
        // No return date = currently loaned

        $loanedEquipment->addLoan($loan);

        // Create available equipment (no loans)
        $availableEquipment = new ClubEquipment();
        $availableEquipment->setClub($club);
        $availableEquipment->setType(ClubEquipmentType::ARROWS);
        $availableEquipment->setName('Available Arrows');

        $this->entityManager->persist($loanedEquipment);
        $this->entityManager->persist($loan);
        $this->entityManager->persist($availableEquipment);
        $this->entityManager->flush();

        $currentlyLoaned = $this->repository->findCurrentlyLoanedByClub($club);

        $this->assertIsArray($currentlyLoaned);
        $this->assertContains($loanedEquipment, $currentlyLoaned);
        $this->assertNotContains($availableEquipment, $currentlyLoaned);
    }

    public function testFindCurrentlyLoanedByClubExcludesReturnedLoans(): void
    {
        $club = $this->entityManager->getRepository(Club::class)
            ->findOneBy(['name' => self::CLUB_NAME_LADG]);
        $this->assertInstanceOf(Club::class, $club);

        $licensees = $this->entityManager->getRepository(Licensee::class)->findAll();
        $this->assertNotEmpty($licensees);
        $licensee = $licensees[0];

        // Create equipment with returned loan
        $equipment = new ClubEquipment();
        $equipment->setClub($club);
        $equipment->setType(ClubEquipmentType::BOW);
        $equipment->setName('Returned Bow');

        $returnedLoan = new EquipmentLoan();
        $returnedLoan->setEquipment($equipment);
        $returnedLoan->setBorrower($licensee);
        $returnedLoan->setStartDate(new \DateTimeImmutable('-7 days'));
        $returnedLoan->setReturnDate(new \DateTimeImmutable('-1 day'));

        $equipment->addLoan($returnedLoan);

        $this->entityManager->persist($equipment);
        $this->entityManager->persist($returnedLoan);
        $this->entityManager->flush();

        $currentlyLoaned = $this->repository->findCurrentlyLoanedByClub($club);

        $this->assertIsArray($currentlyLoaned);
        $this->assertNotContains($equipment, $currentlyLoaned);
    }

    public function testFindByTypeAndClubReturnsOnlyMatchingType(): void
    {
        $club = $this->entityManager->getRepository(Club::class)
            ->findOneBy(['name' => self::CLUB_NAME_LADG]);
        $this->assertInstanceOf(Club::class, $club);

        // Create equipment of different types
        $bow = new ClubEquipment();
        $bow->setClub($club);
        $bow->setType(ClubEquipmentType::BOW);
        $bow->setName('Test Bow');

        $arrows = new ClubEquipment();
        $arrows->setClub($club);
        $arrows->setType(ClubEquipmentType::ARROWS);
        $arrows->setName('Test Arrows');

        $this->entityManager->persist($bow);
        $this->entityManager->persist($arrows);
        $this->entityManager->flush();

        $bows = $this->repository->findByTypeAndClub(ClubEquipmentType::BOW, $club);

        $this->assertIsArray($bows);
        foreach ($bows as $item) {
            $this->assertSame(ClubEquipmentType::BOW, $item->getType());
            $this->assertSame($club->getId(), $item->getClub()->getId());
        }
        $this->assertContains($bow, $bows);
        $this->assertNotContains($arrows, $bows);
    }

    public function testFindByTypeAndClubOrdersByName(): void
    {
        $club = $this->entityManager->getRepository(Club::class)
            ->findOneBy(['name' => self::CLUB_NAME_LADG]);
        $this->assertInstanceOf(Club::class, $club);

        $equipment = $this->repository->findByTypeAndClub(ClubEquipmentType::BOW, $club);

        $this->assertIsArray($equipment);
        // Verify ordering: name ASC
        if (count($equipment) > 1) {
            for ($i = 0; $i < count($equipment) - 1; $i++) {
                $this->assertLessThanOrEqual(
                    $equipment[$i + 1]->getName(),
                    $equipment[$i]->getName()
                );
            }
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
