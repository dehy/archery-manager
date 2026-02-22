<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\DBAL\Types\ClubEquipmentType;
use App\Entity\Club;
use App\Entity\ClubEquipment;
use App\Entity\EquipmentLoan;
use App\Entity\Licensee;
use App\Repository\ClubRepository;
use App\Repository\EquipmentLoanRepository;
use App\Repository\LicenseeRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(EquipmentLoanRepository::class)]
final class EquipmentLoanRepositoryTest extends KernelTestCase
{
    private const string CLUB_NAME_LADG = 'Les Archers de Guyenne';

    private EntityManagerInterface $entityManager;

    private EquipmentLoanRepository $repository;

    private ClubEquipment $equipment;

    private Licensee $borrower;

    #[\Override]
    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        /** @var EquipmentLoanRepository $repository */
        $repository = $this->entityManager->getRepository(EquipmentLoan::class);
        $this->repository = $repository;

        /** @var ClubRepository $clubRepository */
        $clubRepository = $this->entityManager->getRepository(Club::class);

        $club = $clubRepository->findOneBy(['name' => self::CLUB_NAME_LADG]);
        $this->assertInstanceOf(Club::class, $club);

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $this->entityManager->getRepository(Licensee::class);
        $licensees = $licenseeRepository->findAll();
        $this->assertNotEmpty($licensees);
        $this->borrower = $licensees[0];

        // Create test equipment
        $this->equipment = new ClubEquipment();
        $this->equipment->setClub($club);
        $this->equipment->setType(ClubEquipmentType::BOW);
        $this->equipment->setName('Test Bow');

        $this->entityManager->persist($this->equipment);
        $this->entityManager->flush();
    }

    public function testFindActiveLoansReturnsOnlyLoansWithoutReturnDate(): void
    {
        // Create active loan
        $activeLoan = new EquipmentLoan();
        $activeLoan->setEquipment($this->equipment);
        $activeLoan->setBorrower($this->borrower);
        $activeLoan->setStartDate(new \DateTimeImmutable('-10 days'));

        $this->entityManager->persist($activeLoan);

        // Create returned loan
        $returnedLoan = new EquipmentLoan();
        $returnedLoan->setEquipment($this->equipment);
        $returnedLoan->setBorrower($this->borrower);
        $returnedLoan->setStartDate(new \DateTimeImmutable('-30 days'));
        $returnedLoan->setReturnDate(new \DateTimeImmutable('-20 days'));

        $this->entityManager->persist($returnedLoan);

        $this->entityManager->flush();

        $result = $this->repository->findActiveLoans();

        $this->assertNotEmpty($result);
        foreach ($result as $loan) {
            $this->assertNull($loan->getReturnDate());
        }

        $this->assertContains($activeLoan, $result);
        $this->assertNotContains($returnedLoan, $result);
    }

    public function testFindActiveLoansOrdersByStartDateDescending(): void
    {
        // Create older loan
        $olderLoan = new EquipmentLoan();
        $olderLoan->setEquipment($this->equipment);
        $olderLoan->setBorrower($this->borrower);
        $olderLoan->setStartDate(new \DateTimeImmutable('-30 days'));

        $this->entityManager->persist($olderLoan);

        // Create newer loan
        $newerLoan = new EquipmentLoan();
        $newerLoan->setEquipment($this->equipment);
        $newerLoan->setBorrower($this->borrower);
        $newerLoan->setStartDate(new \DateTimeImmutable('-5 days'));

        $this->entityManager->persist($newerLoan);

        $this->entityManager->flush();

        $result = $this->repository->findActiveLoans();

        $this->assertNotEmpty($result);
        $firstLoan = reset($result);
        $this->assertInstanceOf(EquipmentLoan::class, $firstLoan);
        // Newest loan should be first
        $this->assertGreaterThan(
            $olderLoan->getStartDate()->getTimestamp(),
            $firstLoan->getStartDate()->getTimestamp()
        );
    }

    public function testFindByEquipmentReturnsAllLoansForEquipment(): void
    {
        $result = $this->repository->findByEquipment($this->equipment);

        $this->assertIsArray($result);
        foreach ($result as $loan) {
            $this->assertSame($this->equipment, $loan->getEquipment());
        }
    }

    public function testFindByEquipmentOrdersByStartDateDescending(): void
    {
        // Create loan with older start date
        $olderLoan = new EquipmentLoan();
        $olderLoan->setEquipment($this->equipment);
        $olderLoan->setBorrower($this->borrower);
        $olderLoan->setStartDate(new \DateTimeImmutable('-30 days'));
        $olderLoan->setReturnDate(new \DateTimeImmutable('-25 days'));

        $this->entityManager->persist($olderLoan);

        // Create loan with newer start date
        $newerLoan = new EquipmentLoan();
        $newerLoan->setEquipment($this->equipment);
        $newerLoan->setBorrower($this->borrower);
        $newerLoan->setStartDate(new \DateTimeImmutable('-5 days'));
        $newerLoan->setReturnDate(new \DateTimeImmutable('-1 day'));

        $this->entityManager->persist($newerLoan);

        $this->entityManager->flush();

        $result = $this->repository->findByEquipment($this->equipment);

        $this->assertCount(2, $result);
        $this->assertSame($newerLoan, $result[0]);
        $this->assertSame($olderLoan, $result[1]);
    }

    public function testFindByBorrowerReturnsAllLoansForLicensee(): void
    {
        $result = $this->repository->findByBorrower($this->borrower);

        $this->assertIsArray($result);
        foreach ($result as $loan) {
            $this->assertSame($this->borrower, $loan->getBorrower());
        }
    }

    public function testFindActiveLoansByBorrowerReturnsOnlyActiveLoans(): void
    {
        // Create active loan
        $activeLoan = new EquipmentLoan();
        $activeLoan->setEquipment($this->equipment);
        $activeLoan->setBorrower($this->borrower);
        $activeLoan->setStartDate(new \DateTimeImmutable('-5 days'));

        $this->entityManager->persist($activeLoan);

        // Create returned loan
        $returnedLoan = new EquipmentLoan();
        $returnedLoan->setEquipment($this->equipment);
        $returnedLoan->setBorrower($this->borrower);
        $returnedLoan->setStartDate(new \DateTimeImmutable('-20 days'));
        $returnedLoan->setReturnDate(new \DateTimeImmutable('-10 days'));

        $this->entityManager->persist($returnedLoan);

        $this->entityManager->flush();

        $result = $this->repository->findActiveLoansByBorrower($this->borrower);

        $this->assertNotEmpty($result);
        foreach ($result as $loan) {
            $this->assertSame($this->borrower, $loan->getBorrower());
            $this->assertNull($loan->getReturnDate());
        }

        $this->assertContains($activeLoan, $result);
        $this->assertNotContains($returnedLoan, $result);
    }

    public function testFindActiveLoansForEquipmentReturnsActiveLoan(): void
    {
        // Create active loan
        $activeLoan = new EquipmentLoan();
        $activeLoan->setEquipment($this->equipment);
        $activeLoan->setBorrower($this->borrower);
        $activeLoan->setStartDate(new \DateTimeImmutable('-3 days'));

        $this->entityManager->persist($activeLoan);

        $this->entityManager->flush();

        $result = $this->repository->findActiveLoansForEquipment($this->equipment);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContains($activeLoan, $result);
        foreach ($result as $loan) {
            $this->assertNull($loan->getReturnDate());
        }
    }

    public function testFindActiveLoansForEquipmentReturnsEmptyWhenNoActiveLoan(): void
    {
        // Create returned loan
        $returnedLoan = new EquipmentLoan();
        $returnedLoan->setEquipment($this->equipment);
        $returnedLoan->setBorrower($this->borrower);
        $returnedLoan->setStartDate(new \DateTimeImmutable('-10 days'));
        $returnedLoan->setReturnDate(new \DateTimeImmutable('-5 days'));

        $this->entityManager->persist($returnedLoan);

        $this->entityManager->flush();

        $result = $this->repository->findActiveLoansForEquipment($this->equipment);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
