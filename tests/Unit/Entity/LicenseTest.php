<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\Club;
use App\Entity\License;
use App\Entity\Licensee;
use App\Helper\SyncReturnValues;
use PHPUnit\Framework\TestCase;

final class LicenseTest extends TestCase
{
    public function testLicenseCanBeCreated(): void
    {
        $license = new License();

        $this->assertNull($license->getId());
        $this->assertNull($license->getSeason());
        $this->assertNull($license->getLicensee());
        $this->assertNull($license->getClub());
    }

    public function testSetAndGetSeason(): void
    {
        $license = new License();
        $license->setSeason(2025);

        $this->assertSame(2025, $license->getSeason());
    }

    public function testSetAndGetType(): void
    {
        $license = new License();
        $license->setType(LicenseType::ADULTES_CLUB);

        $this->assertSame(LicenseType::ADULTES_CLUB, $license->getType());
    }

    public function testSetAndGetCategory(): void
    {
        $license = new License();
        $license->setCategory(LicenseCategoryType::ADULTES);

        $this->assertSame(LicenseCategoryType::ADULTES, $license->getCategory());
    }

    public function testSetAndGetAgeCategory(): void
    {
        $license = new License();
        $license->setAgeCategory(LicenseAgeCategoryType::SENIOR_1);

        $this->assertSame(LicenseAgeCategoryType::SENIOR_1, $license->getAgeCategory());
    }

    public function testSetAndGetLicensee(): void
    {
        $license = new License();
        $licensee = $this->createMock(Licensee::class);

        $licensee->expects($this->once())
            ->method('addLicense')
            ->with($license);

        $license->setLicensee($licensee);

        $this->assertSame($licensee, $license->getLicensee());
    }

    public function testSetAndGetActivities(): void
    {
        $license = new License();
        $activities = [LicenseActivityType::CL, LicenseActivityType::CO];

        $license->setActivities($activities);

        $this->assertSame($activities, $license->getActivities());
    }

    public function testSetAndGetClub(): void
    {
        $license = new License();
        $club = $this->createMock(Club::class);

        $license->setClub($club);

        $this->assertSame($club, $license->getClub());
    }

    public function testMergeWithWhenLicensesAreDifferent(): void
    {
        $license1 = new License();
        $license1->setSeason(2024)
            ->setType(LicenseType::ADULTES_CLUB)
            ->setCategory(LicenseCategoryType::ADULTES)
            ->setAgeCategory(LicenseAgeCategoryType::SENIOR_1)
            ->setActivities([LicenseActivityType::CL]);

        $license2 = new License();
        $license2->setSeason(2025)
            ->setType(LicenseType::JEUNES)
            ->setCategory(LicenseCategoryType::JEUNES)
            ->setAgeCategory(LicenseAgeCategoryType::U15)
            ->setActivities([LicenseActivityType::CO, LicenseActivityType::TL]);

        $result = $license1->mergeWith($license2);

        $this->assertSame(SyncReturnValues::UPDATED, $result);
        $this->assertSame(2025, $license1->getSeason());
        $this->assertSame(LicenseType::JEUNES, $license1->getType());
        $this->assertSame(LicenseCategoryType::JEUNES, $license1->getCategory());
        $this->assertSame(LicenseAgeCategoryType::U15, $license1->getAgeCategory());
        $this->assertSame([LicenseActivityType::CO, LicenseActivityType::TL], $license1->getActivities());
    }

    public function testMergeWithWhenLicensesAreEqual(): void
    {
        $license1 = new License();
        $license1->setSeason(2025)
            ->setType(LicenseType::ADULTES_CLUB)
            ->setCategory(LicenseCategoryType::ADULTES)
            ->setAgeCategory(LicenseAgeCategoryType::SENIOR_1)
            ->setActivities([LicenseActivityType::CL]);

        $license2 = new License();
        $license2->setSeason(2025)
            ->setType(LicenseType::ADULTES_CLUB)
            ->setCategory(LicenseCategoryType::ADULTES)
            ->setAgeCategory(LicenseAgeCategoryType::SENIOR_1)
            ->setActivities([LicenseActivityType::CL]);

        $result = $license1->mergeWith($license2);

        $this->assertSame(SyncReturnValues::UNTOUCHED, $result);
    }

    public function testFluentInterface(): void
    {
        $license = new License();
        $licensee = $this->createMock(Licensee::class);
        $club = $this->createMock(Club::class);
        $licensee->method('addLicense');

        $result = $license
            ->setSeason(2025)
            ->setType(LicenseType::ADULTES_CLUB)
            ->setCategory(LicenseCategoryType::ADULTES)
            ->setAgeCategory(LicenseAgeCategoryType::SENIOR_1)
            ->setActivities([LicenseActivityType::CL])
            ->setLicensee($licensee)
            ->setClub($club);

        $this->assertSame($license, $result);
    }
}
