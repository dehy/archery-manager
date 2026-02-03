<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ClubEquipment;
use App\Entity\EquipmentLoan;
use App\Entity\Licensee;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EquipmentLoan::class)]
final class EquipmentLoanTest extends TestCase
{
    private const string START_DATE = '2025-01-01';
    private const string RETURN_DATE = '2025-02-01';

    public function testConstructorInitializesTimestamps(): void
    {
        $loan = new EquipmentLoan();

        $this->assertInstanceOf(\DateTimeImmutable::class, $loan->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $loan->getStartDate());
        $this->assertNull($loan->getReturnDate());
    }

    public function testGetId(): void
    {
        $loan = new EquipmentLoan();

        $this->assertNull($loan->getId());
    }

    public function testGetAndSetEquipment(): void
    {
        $loan = new EquipmentLoan();
        $equipment = $this->createMock(ClubEquipment::class);

        $result = $loan->setEquipment($equipment);

        $this->assertSame($loan, $result);
        $this->assertSame($equipment, $loan->getEquipment());
    }

    public function testGetAndSetBorrower(): void
    {
        $loan = new EquipmentLoan();
        $borrower = $this->createMock(Licensee::class);

        $result = $loan->setBorrower($borrower);

        $this->assertSame($loan, $result);
        $this->assertSame($borrower, $loan->getBorrower());
    }

    public function testGetAndSetStartDate(): void
    {
        $loan = new EquipmentLoan();
        $startDate = new \DateTimeImmutable(self::START_DATE);

        $result = $loan->setStartDate($startDate);

        $this->assertSame($loan, $result);
        $this->assertSame($startDate, $loan->getStartDate());
    }

    public function testGetAndSetReturnDate(): void
    {
        $loan = new EquipmentLoan();
        $returnDate = new \DateTimeImmutable(self::RETURN_DATE);

        $result = $loan->setReturnDate($returnDate);

        $this->assertSame($loan, $result);
        $this->assertSame($returnDate, $loan->getReturnDate());
    }

    public function testReturnDateCanBeNull(): void
    {
        $loan = new EquipmentLoan();
        $returnDate = new \DateTimeImmutable(self::RETURN_DATE);

        $loan->setReturnDate($returnDate);
        $this->assertSame($returnDate, $loan->getReturnDate());

        $loan->setReturnDate(null);
        $this->assertNull($loan->getReturnDate());
    }

    public function testGetAndSetNotes(): void
    {
        $loan = new EquipmentLoan();
        $notes = 'Equipment in good condition';

        $result = $loan->setNotes($notes);

        $this->assertSame($loan, $result);
        $this->assertSame($notes, $loan->getNotes());
    }

    public function testNotesCanBeNull(): void
    {
        $loan = new EquipmentLoan();

        $loan->setNotes('Some notes');
        $this->assertSame('Some notes', $loan->getNotes());

        $loan->setNotes(null);
        $this->assertNull($loan->getNotes());
    }

    public function testGetAndSetCreatedBy(): void
    {
        $loan = new EquipmentLoan();
        $user = $this->createMock(User::class);

        $result = $loan->setCreatedBy($user);

        $this->assertSame($loan, $result);
        $this->assertSame($user, $loan->getCreatedBy());
    }

    public function testCreatedByCanBeNull(): void
    {
        $loan = new EquipmentLoan();

        $this->assertNull($loan->getCreatedBy());

        $user = $this->createMock(User::class);
        $loan->setCreatedBy($user);
        $this->assertSame($user, $loan->getCreatedBy());

        $loan->setCreatedBy(null);
        $this->assertNull($loan->getCreatedBy());
    }

    public function testIsActiveReturnsTrueWhenReturnDateIsNull(): void
    {
        $loan = new EquipmentLoan();

        $this->assertTrue($loan->isActive());
    }

    public function testIsActiveReturnsFalseWhenReturnDateIsSet(): void
    {
        $loan = new EquipmentLoan();
        $loan->setReturnDate(new \DateTimeImmutable(self::RETURN_DATE));

        $this->assertFalse($loan->isActive());
    }

    public function testGetLoanDurationForActiveLoan(): void
    {
        $loan = new EquipmentLoan();
        $startDate = new \DateTimeImmutable(self::START_DATE);
        $loan->setStartDate($startDate);

        $duration = $loan->getLoanDuration();

        $this->assertInstanceOf(\DateInterval::class, $duration);
        // Duration should be from start date to now
        $expectedDays = $startDate->diff(new \DateTimeImmutable())->days;
        $this->assertSame($expectedDays, (int) $duration->format('%a'));
    }

    public function testGetLoanDurationForReturnedLoan(): void
    {
        $loan = new EquipmentLoan();
        $startDate = new \DateTimeImmutable(self::START_DATE);
        $returnDate = new \DateTimeImmutable('2025-01-15');
        $loan->setStartDate($startDate);
        $loan->setReturnDate($returnDate);

        $duration = $loan->getLoanDuration();

        $this->assertInstanceOf(\DateInterval::class, $duration);
        $this->assertSame(14, (int) $duration->format('%a'));
    }

    public function testGetLoanDurationInDaysForActiveLoan(): void
    {
        $loan = new EquipmentLoan();
        $startDate = new \DateTimeImmutable(self::START_DATE);
        $loan->setStartDate($startDate);

        $days = $loan->getLoanDurationInDays();

        $this->assertIsInt($days);
        $expectedDays = $startDate->diff(new \DateTimeImmutable())->days;
        $this->assertSame($expectedDays, $days);
    }

    public function testGetLoanDurationInDaysForReturnedLoan(): void
    {
        $loan = new EquipmentLoan();
        $startDate = new \DateTimeImmutable(self::START_DATE);
        $returnDate = new \DateTimeImmutable('2025-01-15');
        $loan->setStartDate($startDate);
        $loan->setReturnDate($returnDate);

        $days = $loan->getLoanDurationInDays();

        $this->assertSame(14, $days);
    }

    public function testGetLoanDurationInDaysHandlesZeroDayLoans(): void
    {
        $loan = new EquipmentLoan();
        $sameDate = new \DateTimeImmutable('2025-01-01 10:00:00');
        $loan->setStartDate($sameDate);
        $loan->setReturnDate(new \DateTimeImmutable('2025-01-01 15:00:00'));

        $days = $loan->getLoanDurationInDays();

        $this->assertSame(0, $days);
    }
}
