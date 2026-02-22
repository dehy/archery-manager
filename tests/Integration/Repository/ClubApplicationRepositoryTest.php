<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\DBAL\Types\ClubApplicationStatusType;
use App\Entity\Club;
use App\Entity\ClubApplication;
use App\Entity\Licensee;
use App\Repository\ClubApplicationRepository;
use App\Repository\ClubRepository;
use App\Repository\LicenseeRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(ClubApplicationRepository::class)]
final class ClubApplicationRepositoryTest extends KernelTestCase
{
    private const string CLUB_NAME_LADG = 'Les Archers de Guyenne';

    private const int TEST_SEASON = 2025;

    private EntityManagerInterface $entityManager;

    private ClubApplicationRepository $repository;

    private Club $club;

    private Licensee $licensee;

    #[\Override]
    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        /** @var ClubApplicationRepository $repository */
        $repository = $this->entityManager->getRepository(ClubApplication::class);
        $this->repository = $repository;

        /** @var ClubRepository $clubRepository */
        $clubRepository = $this->entityManager->getRepository(Club::class);
        $club = $clubRepository->findOneBy(['name' => self::CLUB_NAME_LADG]);
        $this->assertInstanceOf(Club::class, $club);
        $this->club = $club;

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $this->entityManager->getRepository(Licensee::class);
        $licensees = $licenseeRepository->findAll();
        $this->assertNotEmpty($licensees);
        $this->licensee = $licensees[0];
    }

    public function testAdd(): void
    {
        $application = new ClubApplication();
        $application->setLicensee($this->licensee);
        $application->setClub($this->club);
        $application->setSeason(self::TEST_SEASON);

        $this->repository->add($application, true);

        $this->assertNotNull($application->getId());
    }

    public function testAddWithoutFlush(): void
    {
        $application = new ClubApplication();
        $application->setLicensee($this->licensee);
        $application->setClub($this->club);
        $application->setSeason(self::TEST_SEASON);

        $this->repository->add($application, false);
        $this->entityManager->flush();

        $this->assertNotNull($application->getId());
    }

    public function testRemove(): void
    {
        $application = new ClubApplication();
        $application->setLicensee($this->licensee);
        $application->setClub($this->club);
        $application->setSeason(self::TEST_SEASON);

        $this->repository->add($application, true);
        $id = $application->getId();
        $this->assertNotNull($id);

        $this->repository->remove($application, true);

        $removed = $this->repository->find($id);
        $this->assertNotInstanceOf(ClubApplication::class, $removed);
    }

    public function testRemoveWithoutFlush(): void
    {
        $application = new ClubApplication();
        $application->setLicensee($this->licensee);
        $application->setClub($this->club);
        $application->setSeason(self::TEST_SEASON);

        $this->repository->add($application, true);
        $id = $application->getId();

        $this->repository->remove($application, false);
        $this->entityManager->flush();

        $removed = $this->repository->find($id);
        $this->assertNotInstanceOf(ClubApplication::class, $removed);
    }

    public function testFindPendingByClubAndSeasonReturnsOnlyPendingApplications(): void
    {
        // Create pending application
        $pendingApp = new ClubApplication();
        $pendingApp->setLicensee($this->licensee);
        $pendingApp->setClub($this->club);
        $pendingApp->setSeason(self::TEST_SEASON);
        $pendingApp->setStatus(ClubApplicationStatusType::PENDING);

        $this->repository->add($pendingApp, false);

        // Create validated application
        $validatedApp = new ClubApplication();
        $validatedApp->setLicensee($this->licensee);
        $validatedApp->setClub($this->club);
        $validatedApp->setSeason(self::TEST_SEASON);
        $validatedApp->setStatus(ClubApplicationStatusType::VALIDATED);

        $this->repository->add($validatedApp, false);

        $this->entityManager->flush();

        $result = $this->repository->findPendingByClubAndSeason($this->club, self::TEST_SEASON);

        $this->assertNotEmpty($result);
        foreach ($result as $application) {
            $this->assertSame(ClubApplicationStatusType::PENDING, $application->getStatus());
            $this->assertSame($this->club, $application->getClub());
            $this->assertSame(self::TEST_SEASON, $application->getSeason());
        }

        $this->assertContains($pendingApp, $result);
        $this->assertNotContains($validatedApp, $result);
    }

    public function testFindPendingByClubAndSeasonOrdersByCreatedAtAscending(): void
    {
        // Create older application
        $olderApp = new ClubApplication();
        $olderApp->setLicensee($this->licensee);
        $olderApp->setClub($this->club);
        $olderApp->setSeason(self::TEST_SEASON);
        $olderApp->setCreatedAt(new \DateTimeImmutable('-10 days'));

        $this->repository->add($olderApp, false);

        // Create newer application
        $newerApp = new ClubApplication();
        $newerApp->setLicensee($this->licensee);
        $newerApp->setClub($this->club);
        $newerApp->setSeason(self::TEST_SEASON);
        $newerApp->setCreatedAt(new \DateTimeImmutable('-5 days'));

        $this->repository->add($newerApp, false);

        $this->entityManager->flush();

        $result = $this->repository->findPendingByClubAndSeason($this->club, self::TEST_SEASON);

        $this->assertCount(2, $result);
        // Older application should be first (ASC order)
        $this->assertSame($olderApp, $result[0]);
        $this->assertSame($newerApp, $result[1]);
    }

    public function testFindByClubAndSeasonReturnsAllApplications(): void
    {
        // Create applications with different statuses
        $pendingApp = new ClubApplication();
        $pendingApp->setLicensee($this->licensee);
        $pendingApp->setClub($this->club);
        $pendingApp->setSeason(self::TEST_SEASON);
        $pendingApp->setStatus(ClubApplicationStatusType::PENDING);

        $this->repository->add($pendingApp, false);

        $validatedApp = new ClubApplication();
        $validatedApp->setLicensee($this->licensee);
        $validatedApp->setClub($this->club);
        $validatedApp->setSeason(self::TEST_SEASON);
        $validatedApp->setStatus(ClubApplicationStatusType::VALIDATED);

        $this->repository->add($validatedApp, false);

        $rejectedApp = new ClubApplication();
        $rejectedApp->setLicensee($this->licensee);
        $rejectedApp->setClub($this->club);
        $rejectedApp->setSeason(self::TEST_SEASON);
        $rejectedApp->setStatus(ClubApplicationStatusType::REJECTED);

        $this->repository->add($rejectedApp, false);

        $this->entityManager->flush();

        $result = $this->repository->findByClubAndSeason($this->club, self::TEST_SEASON);

        $this->assertCount(3, $result);
        foreach ($result as $application) {
            $this->assertSame($this->club, $application->getClub());
            $this->assertSame(self::TEST_SEASON, $application->getSeason());
        }

        $this->assertContains($pendingApp, $result);
        $this->assertContains($validatedApp, $result);
        $this->assertContains($rejectedApp, $result);
    }

    public function testFindByClubAndSeasonOrdersByCreatedAtDescending(): void
    {
        // Create older application
        $olderApp = new ClubApplication();
        $olderApp->setLicensee($this->licensee);
        $olderApp->setClub($this->club);
        $olderApp->setSeason(self::TEST_SEASON);
        $olderApp->setCreatedAt(new \DateTimeImmutable('-10 days'));

        $this->repository->add($olderApp, false);

        // Create newer application
        $newerApp = new ClubApplication();
        $newerApp->setLicensee($this->licensee);
        $newerApp->setClub($this->club);
        $newerApp->setSeason(self::TEST_SEASON);
        $newerApp->setCreatedAt(new \DateTimeImmutable('-5 days'));

        $this->repository->add($newerApp, false);

        $this->entityManager->flush();

        $result = $this->repository->findByClubAndSeason($this->club, self::TEST_SEASON);

        $this->assertCount(2, $result);
        // Newer application should be first (DESC order)
        $this->assertSame($newerApp, $result[0]);
        $this->assertSame($olderApp, $result[1]);
    }

    public function testFindByLicenseeAndSeasonReturnsAllApplicationsForLicensee(): void
    {
        // Create application
        $application = new ClubApplication();
        $application->setLicensee($this->licensee);
        $application->setClub($this->club);
        $application->setSeason(self::TEST_SEASON);

        $this->repository->add($application, true);

        $result = $this->repository->findByLicenseeAndSeason($this->licensee, self::TEST_SEASON);

        $this->assertNotEmpty($result);
        foreach ($result as $app) {
            $this->assertSame($this->licensee, $app->getLicensee());
            $this->assertSame(self::TEST_SEASON, $app->getSeason());
        }

        $this->assertContains($application, $result);
    }

    public function testFindByLicenseeAndSeasonOrdersByCreatedAtDescending(): void
    {
        // Create older application
        $olderApp = new ClubApplication();
        $olderApp->setLicensee($this->licensee);
        $olderApp->setClub($this->club);
        $olderApp->setSeason(self::TEST_SEASON);
        $olderApp->setCreatedAt(new \DateTimeImmutable('-10 days'));

        $this->repository->add($olderApp, false);

        // Create newer application
        $newerApp = new ClubApplication();
        $newerApp->setLicensee($this->licensee);
        $newerApp->setClub($this->club);
        $newerApp->setSeason(self::TEST_SEASON);
        $newerApp->setCreatedAt(new \DateTimeImmutable('-5 days'));

        $this->repository->add($newerApp, false);

        $this->entityManager->flush();

        $result = $this->repository->findByLicenseeAndSeason($this->licensee, self::TEST_SEASON);

        $this->assertCount(2, $result);
        // Newer application should be first (DESC order)
        $this->assertSame($newerApp, $result[0]);
        $this->assertSame($olderApp, $result[1]);
    }
}
