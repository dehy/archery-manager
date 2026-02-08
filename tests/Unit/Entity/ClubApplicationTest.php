<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\DBAL\Types\ClubApplicationStatusType;
use App\Entity\Club;
use App\Entity\ClubApplication;
use App\Entity\Licensee;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClubApplication::class)]
final class ClubApplicationTest extends TestCase
{
    public function testConstructorInitializesCreatedAtAndDefaultStatus(): void
    {
        $application = new ClubApplication();

        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getCreatedAt());
        $this->assertSame(ClubApplicationStatusType::PENDING, $application->getStatus());
    }

    public function testGetId(): void
    {
        $application = new ClubApplication();

        $this->assertNull($application->getId());
    }

    public function testGetAndSetLicensee(): void
    {
        $application = new ClubApplication();
        $licensee = $this->createMock(Licensee::class);

        $result = $application->setLicensee($licensee);

        $this->assertSame($application, $result);
        $this->assertSame($licensee, $application->getLicensee());
    }

    public function testLicenseeCanBeNull(): void
    {
        $application = new ClubApplication();
        $licensee = $this->createMock(Licensee::class);

        $application->setLicensee($licensee);
        $this->assertSame($licensee, $application->getLicensee());

        $application->setLicensee(null);
        $this->assertNull($application->getLicensee());
    }

    public function testGetAndSetClub(): void
    {
        $application = new ClubApplication();
        $club = $this->createMock(Club::class);

        $result = $application->setClub($club);

        $this->assertSame($application, $result);
        $this->assertSame($club, $application->getClub());
    }

    public function testClubCanBeNull(): void
    {
        $application = new ClubApplication();
        $club = $this->createMock(Club::class);

        $application->setClub($club);
        $this->assertSame($club, $application->getClub());

        $application->setClub(null);
        $this->assertNull($application->getClub());
    }

    public function testGetAndSetSeason(): void
    {
        $application = new ClubApplication();
        $season = 2025;

        $result = $application->setSeason($season);

        $this->assertSame($application, $result);
        $this->assertSame($season, $application->getSeason());
    }

    public function testSeasonInitiallyNull(): void
    {
        $application = new ClubApplication();

        $this->assertNull($application->getSeason());
    }

    public function testGetAndSetStatus(): void
    {
        $application = new ClubApplication();
        $status = ClubApplicationStatusType::VALIDATED;

        $result = $application->setStatus($status);

        $this->assertSame($application, $result);
        $this->assertSame($status, $application->getStatus());
    }

    public function testSetStatusUpdatesUpdatedAt(): void
    {
        $application = new ClubApplication();
        $this->assertNotInstanceOf(\DateTimeImmutable::class, $application->getUpdatedAt());

        $application->setStatus(ClubApplicationStatusType::VALIDATED);

        $this->assertInstanceOf(\DateTimeImmutable::class, $application->getUpdatedAt());
    }

    public function testGetAndSetRejectionReason(): void
    {
        $application = new ClubApplication();
        $reason = 'Incomplete documentation';

        $result = $application->setRejectionReason($reason);

        $this->assertSame($application, $result);
        $this->assertSame($reason, $application->getRejectionReason());
    }

    public function testRejectionReasonCanBeNull(): void
    {
        $application = new ClubApplication();

        $this->assertNull($application->getRejectionReason());

        $application->setRejectionReason('Some reason');
        $this->assertSame('Some reason', $application->getRejectionReason());

        $application->setRejectionReason(null);
        $this->assertNull($application->getRejectionReason());
    }

    public function testGetAndSetCreatedAt(): void
    {
        $application = new ClubApplication();
        $createdAt = new \DateTimeImmutable('2025-01-01');

        $result = $application->setCreatedAt($createdAt);

        $this->assertSame($application, $result);
        $this->assertSame($createdAt, $application->getCreatedAt());
    }

    public function testGetAndSetUpdatedAt(): void
    {
        $application = new ClubApplication();
        $updatedAt = new \DateTimeImmutable('2025-02-01');

        $result = $application->setUpdatedAt($updatedAt);

        $this->assertSame($application, $result);
        $this->assertSame($updatedAt, $application->getUpdatedAt());
    }

    public function testUpdatedAtCanBeNull(): void
    {
        $application = new ClubApplication();

        $this->assertNotInstanceOf(\DateTimeImmutable::class, $application->getUpdatedAt());

        $updatedAt = new \DateTimeImmutable('2025-02-01');
        $application->setUpdatedAt($updatedAt);
        $this->assertSame($updatedAt, $application->getUpdatedAt());

        $application->setUpdatedAt(null);
        $this->assertNull($application->getUpdatedAt());
    }

    public function testGetAndSetProcessedBy(): void
    {
        $application = new ClubApplication();
        $user = $this->createMock(User::class);

        $result = $application->setProcessedBy($user);

        $this->assertSame($application, $result);
        $this->assertSame($user, $application->getProcessedBy());
    }

    public function testProcessedByCanBeNull(): void
    {
        $application = new ClubApplication();

        $this->assertNotInstanceOf(User::class, $application->getProcessedBy());

        $user = $this->createMock(User::class);
        $application->setProcessedBy($user);
        $this->assertSame($user, $application->getProcessedBy());

        $application->setProcessedBy(null);
        $this->assertNull($application->getProcessedBy());
    }

    public function testIsPendingReturnsTrueForPendingStatus(): void
    {
        $application = new ClubApplication();
        $application->setStatus(ClubApplicationStatusType::PENDING);

        $this->assertTrue($application->isPending());
        $this->assertFalse($application->isValidated());
        $this->assertFalse($application->isOnWaitingList());
        $this->assertFalse($application->isRejected());
    }

    public function testIsValidatedReturnsTrueForValidatedStatus(): void
    {
        $application = new ClubApplication();
        $application->setStatus(ClubApplicationStatusType::VALIDATED);

        $this->assertFalse($application->isPending());
        $this->assertTrue($application->isValidated());
        $this->assertFalse($application->isOnWaitingList());
        $this->assertFalse($application->isRejected());
    }

    public function testIsOnWaitingListReturnsTrueForWaitingListStatus(): void
    {
        $application = new ClubApplication();
        $application->setStatus(ClubApplicationStatusType::WAITING_LIST);

        $this->assertFalse($application->isPending());
        $this->assertFalse($application->isValidated());
        $this->assertTrue($application->isOnWaitingList());
        $this->assertFalse($application->isRejected());
    }

    public function testIsRejectedReturnsTrueForRejectedStatus(): void
    {
        $application = new ClubApplication();
        $application->setStatus(ClubApplicationStatusType::REJECTED);

        $this->assertFalse($application->isPending());
        $this->assertFalse($application->isValidated());
        $this->assertFalse($application->isOnWaitingList());
        $this->assertTrue($application->isRejected());
    }

    public function testDefaultStatusIsPending(): void
    {
        $application = new ClubApplication();

        $this->assertTrue($application->isPending());
    }
}
