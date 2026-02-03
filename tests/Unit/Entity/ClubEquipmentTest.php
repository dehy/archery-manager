<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\DBAL\Types\ArrowType;
use App\DBAL\Types\BowType;
use App\DBAL\Types\ClubEquipmentType;
use App\DBAL\Types\FletchingType;
use App\Entity\Club;
use App\Entity\ClubEquipment;
use App\Entity\EquipmentLoan;
use PHPUnit\Framework\TestCase;

final class ClubEquipmentTest extends TestCase
{
    private ClubEquipment $clubEquipment;

    protected function setUp(): void
    {
        $this->clubEquipment = new ClubEquipment();
    }

    public function testConstructorInitializesCollections(): void
    {
        $equipment = new ClubEquipment();

        $this->assertInstanceOf(\DateTimeImmutable::class, $equipment->getCreatedAt());
        $this->assertCount(0, $equipment->getLoans());
    }

    public function testGetId(): void
    {
        $this->assertNull($this->clubEquipment->getId());
    }

    public function testClubGetterSetter(): void
    {
        $club = new Club();
        $club->setName('Test Club');

        $result = $this->clubEquipment->setClub($club);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame($club, $this->clubEquipment->getClub());
    }

    public function testTypeGetterSetter(): void
    {
        $result = $this->clubEquipment->setType(ClubEquipmentType::BOW);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame(ClubEquipmentType::BOW, $this->clubEquipment->getType());
    }

    public function testNameGetterSetter(): void
    {
        $result = $this->clubEquipment->setName('Test Equipment');

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame('Test Equipment', $this->clubEquipment->getName());
    }

    public function testSerialNumberGetterSetter(): void
    {
        $result = $this->clubEquipment->setSerialNumber('SN123456');

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame('SN123456', $this->clubEquipment->getSerialNumber());
    }

    public function testSerialNumberCanBeNull(): void
    {
        $this->clubEquipment->setSerialNumber('SN123');
        $result = $this->clubEquipment->setSerialNumber(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getSerialNumber());
    }

    public function testCountGetterSetter(): void
    {
        $result = $this->clubEquipment->setCount(5);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame(5, $this->clubEquipment->getCount());
    }

    public function testCountCanBeNull(): void
    {
        $this->clubEquipment->setCount(3);
        $result = $this->clubEquipment->setCount(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getCount());
    }

    public function testBowTypeGetterSetter(): void
    {
        $result = $this->clubEquipment->setBowType(BowType::CLASSIQUE_COMPETITION);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame(BowType::CLASSIQUE_COMPETITION, $this->clubEquipment->getBowType());
    }

    public function testBowTypeCanBeNull(): void
    {
        $this->clubEquipment->setBowType(BowType::POULIES);
        $result = $this->clubEquipment->setBowType(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getBowType());
    }

    public function testBrandGetterSetter(): void
    {
        $result = $this->clubEquipment->setBrand('Hoyt');

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame('Hoyt', $this->clubEquipment->getBrand());
    }

    public function testBrandCanBeNull(): void
    {
        $this->clubEquipment->setBrand('Hoyt');
        $result = $this->clubEquipment->setBrand(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getBrand());
    }

    public function testModelGetterSetter(): void
    {
        $result = $this->clubEquipment->setModel('Formula X1');

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame('Formula X1', $this->clubEquipment->getModel());
    }

    public function testModelCanBeNull(): void
    {
        $this->clubEquipment->setModel('Model X');
        $result = $this->clubEquipment->setModel(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getModel());
    }

    public function testLimbSizeGetterSetter(): void
    {
        $result = $this->clubEquipment->setLimbSize(70);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame(70, $this->clubEquipment->getLimbSize());
    }

    public function testLimbSizeCanBeNull(): void
    {
        $this->clubEquipment->setLimbSize(68);
        $result = $this->clubEquipment->setLimbSize(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getLimbSize());
    }

    public function testLimbStrengthGetterSetter(): void
    {
        $result = $this->clubEquipment->setLimbStrength(30);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame(30, $this->clubEquipment->getLimbStrength());
    }

    public function testLimbStrengthCanBeNull(): void
    {
        $this->clubEquipment->setLimbStrength(28);
        $result = $this->clubEquipment->setLimbStrength(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getLimbStrength());
    }

    public function testArrowTypeGetterSetter(): void
    {
        $result = $this->clubEquipment->setArrowType(ArrowType::CARBON);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame(ArrowType::CARBON, $this->clubEquipment->getArrowType());
    }

    public function testArrowTypeCanBeNull(): void
    {
        $this->clubEquipment->setArrowType(ArrowType::ALUMINUM);
        $result = $this->clubEquipment->setArrowType(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getArrowType());
    }

    public function testArrowLengthGetterSetter(): void
    {
        $result = $this->clubEquipment->setArrowLength(28);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame(28, $this->clubEquipment->getArrowLength());
    }

    public function testArrowLengthCanBeNull(): void
    {
        $this->clubEquipment->setArrowLength(29);
        $result = $this->clubEquipment->setArrowLength(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getArrowLength());
    }

    public function testArrowSpineGetterSetter(): void
    {
        $result = $this->clubEquipment->setArrowSpine('500');

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame('500', $this->clubEquipment->getArrowSpine());
    }

    public function testArrowSpineCanBeNull(): void
    {
        $this->clubEquipment->setArrowSpine('600');
        $result = $this->clubEquipment->setArrowSpine(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getArrowSpine());
    }

    public function testFletchingTypeGetterSetter(): void
    {
        $result = $this->clubEquipment->setFletchingType(FletchingType::SPINWINGS);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame(FletchingType::SPINWINGS, $this->clubEquipment->getFletchingType());
    }

    public function testFletchingTypeCanBeNull(): void
    {
        $this->clubEquipment->setFletchingType(FletchingType::PLASTIC);
        $result = $this->clubEquipment->setFletchingType(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getFletchingType());
    }

    public function testNotesGetterSetter(): void
    {
        $result = $this->clubEquipment->setNotes('Special equipment for beginners');

        $this->assertSame($this->clubEquipment, $result);
        $this->assertSame('Special equipment for beginners', $this->clubEquipment->getNotes());
    }

    public function testNotesCanBeNull(): void
    {
        $this->clubEquipment->setNotes('Some notes');
        $result = $this->clubEquipment->setNotes(null);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertNull($this->clubEquipment->getNotes());
    }

    public function testIsAvailableDefaultsToTrue(): void
    {
        $equipment = new ClubEquipment();

        $this->assertTrue($equipment->isAvailable());
    }

    public function testIsAvailableGetterSetter(): void
    {
        $result = $this->clubEquipment->setIsAvailable(false);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertFalse($this->clubEquipment->isAvailable());
    }

    public function testIsAvailableCanBeSetBackToTrue(): void
    {
        $this->clubEquipment->setIsAvailable(false);
        $result = $this->clubEquipment->setIsAvailable(true);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertTrue($this->clubEquipment->isAvailable());
    }

    public function testGetLoans(): void
    {
        $loans = $this->clubEquipment->getLoans();

        $this->assertCount(0, $loans);
    }

    public function testAddLoan(): void
    {
        $loan = $this->createMock(EquipmentLoan::class);
        $loan->expects($this->once())
            ->method('setEquipment')
            ->with($this->clubEquipment);

        $result = $this->clubEquipment->addLoan($loan);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertCount(1, $this->clubEquipment->getLoans());
        $this->assertTrue($this->clubEquipment->getLoans()->contains($loan));
    }

    public function testAddLoanDoesNotAddDuplicates(): void
    {
        $loan = $this->createMock(EquipmentLoan::class);
        $loan->expects($this->once())
            ->method('setEquipment')
            ->with($this->clubEquipment);

        $this->clubEquipment->addLoan($loan);
        $this->clubEquipment->addLoan($loan);

        $this->assertCount(1, $this->clubEquipment->getLoans());
    }

    public function testRemoveLoan(): void
    {
        $loan = $this->createMock(EquipmentLoan::class);
        $loan->expects($this->once())
            ->method('setEquipment')
            ->with($this->clubEquipment)
            ->willReturn($loan);

        $this->clubEquipment->addLoan($loan);
        $result = $this->clubEquipment->removeLoan($loan);

        $this->assertSame($this->clubEquipment, $result);
        $this->assertCount(0, $this->clubEquipment->getLoans());
    }

    public function testRemoveLoanThatDoesNotExist(): void
    {
        $result = $this->clubEquipment->removeLoan($this->createMock(EquipmentLoan::class));

        $this->assertSame($this->clubEquipment, $result);
        $this->assertCount(0, $this->clubEquipment->getLoans());
    }

    public function testGetCurrentLoanReturnsNullWhenNoLoans(): void
    {
        $this->assertNotInstanceOf(EquipmentLoan::class, $this->clubEquipment->getCurrentLoan());
    }

    public function testGetCurrentLoanReturnsActiveLoan(): void
    {
        $activeLoan = $this->createMock(EquipmentLoan::class);
        $activeLoan->method('getReturnDate')->willReturn(null);
        $activeLoan->method('setEquipment');

        $returnedLoan = $this->createMock(EquipmentLoan::class);
        $returnedLoan->method('getReturnDate')->willReturn(new \DateTimeImmutable());
        $returnedLoan->method('setEquipment');

        $this->clubEquipment->addLoan($returnedLoan);
        $this->clubEquipment->addLoan($activeLoan);

        $this->assertSame($activeLoan, $this->clubEquipment->getCurrentLoan());
    }

    public function testGetCurrentLoanReturnsNullWhenAllLoansReturned(): void
    {
        $returnedLoan = $this->createMock(EquipmentLoan::class);
        $returnedLoan->method('getReturnDate')->willReturn(new \DateTimeImmutable());
        $returnedLoan->method('setEquipment');

        $this->clubEquipment->addLoan($returnedLoan);

        $this->assertNotInstanceOf(EquipmentLoan::class, $this->clubEquipment->getCurrentLoan());
    }

    public function testIsCurrentlyLoanedReturnsTrueWhenActiveLoanExists(): void
    {
        $activeLoan = $this->createMock(EquipmentLoan::class);
        $activeLoan->method('getReturnDate')->willReturn(null);
        $activeLoan->method('setEquipment');

        $this->clubEquipment->addLoan($activeLoan);

        $this->assertTrue($this->clubEquipment->isCurrentlyLoaned());
    }

    public function testIsCurrentlyLoanedReturnsFalseWhenNoActiveLoans(): void
    {
        $this->assertFalse($this->clubEquipment->isCurrentlyLoaned());
    }

    public function testIsCurrentlyLoanedReturnsFalseWhenAllLoansReturned(): void
    {
        $returnedLoan = $this->createMock(EquipmentLoan::class);
        $returnedLoan->method('getReturnDate')->willReturn(new \DateTimeImmutable());
        $returnedLoan->method('setEquipment');

        $this->clubEquipment->addLoan($returnedLoan);

        $this->assertFalse($this->clubEquipment->isCurrentlyLoaned());
    }
}
