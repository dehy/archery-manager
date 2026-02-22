<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Helper\LicenseeHelper;
use App\Helper\LicenseHelper;
use App\Helper\SeasonHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LicenseHelperTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $seasonHelper;

    private LicenseHelper $licenseHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->seasonHelper = $this->createMock(SeasonHelper::class);
        $this->licenseHelper = new LicenseHelper($this->createStub(LicenseeHelper::class), $this->seasonHelper);
    }

    public function testGetSeasonForDateInSeptember(): void
    {
        $date = new \DateTimeImmutable('2023-09-01');
        $season = LicenseHelper::getSeasonForDate($date);
        $this->assertSame(2024, $season);
    }

    public function testGetSeasonForDateInAugust(): void
    {
        $date = new \DateTimeImmutable('2023-08-31');
        $season = LicenseHelper::getSeasonForDate($date);
        $this->assertSame(2023, $season);
    }

    public function testGetSeasonForDateInJanuary(): void
    {
        $date = new \DateTimeImmutable('2024-01-15');
        $season = LicenseHelper::getSeasonForDate($date);
        $this->assertSame(2024, $season);
    }

    public function testCategoryTypeForAgeCategory(): void
    {
        $this->assertSame(LicenseCategoryType::POUSSINS, $this->licenseHelper->categoryTypeForAgeCategory(LicenseAgeCategoryType::U11));
        $this->assertSame(LicenseCategoryType::JEUNES, $this->licenseHelper->categoryTypeForAgeCategory(LicenseAgeCategoryType::U13));
        $this->assertSame(LicenseCategoryType::JEUNES, $this->licenseHelper->categoryTypeForAgeCategory(LicenseAgeCategoryType::U15));
        $this->assertSame(LicenseCategoryType::JEUNES, $this->licenseHelper->categoryTypeForAgeCategory(LicenseAgeCategoryType::U18));
        $this->assertSame(LicenseCategoryType::JEUNES, $this->licenseHelper->categoryTypeForAgeCategory(LicenseAgeCategoryType::U21));
        $this->assertSame(LicenseCategoryType::ADULTES, $this->licenseHelper->categoryTypeForAgeCategory(LicenseAgeCategoryType::SENIOR_1));
        $this->assertSame(LicenseCategoryType::ADULTES, $this->licenseHelper->categoryTypeForAgeCategory(LicenseAgeCategoryType::SENIOR_2));
        $this->assertSame(LicenseCategoryType::ADULTES, $this->licenseHelper->categoryTypeForAgeCategory(LicenseAgeCategoryType::SENIOR_3));
    }

    public function testLicenseTypeForBirthdateAdulteTournament(): void
    {
        $this->seasonHelper
            ->method('getSelectedSeason')
            ->willReturn(2025);

        // Adult birthdate (born in 1990, 35 years old in 2025)
        $birthdate = new \DateTimeImmutable('1990-06-15');
        $licenseType = $this->licenseHelper->licenseTypeForBirthdate($birthdate, true);

        $this->assertSame(LicenseType::ADULTES_COMPETITION, $licenseType);
    }

    public function testLicenseTypeForBirthdateAdulteClub(): void
    {
        $this->seasonHelper
            ->method('getSelectedSeason')
            ->willReturn(2025);

        // Adult birthdate (born in 1990, 35 years old in 2025)
        $birthdate = new \DateTimeImmutable('1990-06-15');
        $licenseType = $this->licenseHelper->licenseTypeForBirthdate($birthdate, false);

        $this->assertSame(LicenseType::ADULTES_CLUB, $licenseType);
    }

    public function testAgeCategoryForBirthdate2025Season(): void
    {
        $this->seasonHelper
            ->method('getSelectedSeason')
            ->willReturn(2025);

        // Test U11 (born after 2015-01-01)
        $birthdate = new \DateTimeImmutable('2016-06-15');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::U11, $ageCategory);

        // Test U13 (born 2013-01-01 to 2014-12-31)
        $birthdate = new \DateTimeImmutable('2014-06-15');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::U13, $ageCategory);

        // Test U15 (born 2011-01-01 to 2012-12-31)
        $birthdate = new \DateTimeImmutable('2012-06-15');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::U15, $ageCategory);

        // Test U18 (born 2008-01-01 to 2010-12-31)
        $birthdate = new \DateTimeImmutable('2009-06-15');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::U18, $ageCategory);

        // Test U21 (born 2005-01-01 to 2007-12-31)
        $birthdate = new \DateTimeImmutable('2006-06-15');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::U21, $ageCategory);

        // Test SENIOR_1 (born 1986-01-01 to 2004-12-31)
        $birthdate = new \DateTimeImmutable('1995-06-15');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::SENIOR_1, $ageCategory);

        // Test SENIOR_2 (born 1966-01-01 to 1985-12-31)
        $birthdate = new \DateTimeImmutable('1975-06-15');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::SENIOR_2, $ageCategory);

        // Test SENIOR_3 (born before 1966-01-01)
        $birthdate = new \DateTimeImmutable('1960-06-15');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::SENIOR_3, $ageCategory);
    }

    public function testAgeCategoryForBirthdate2024Season(): void
    {
        $this->seasonHelper
            ->method('getSelectedSeason')
            ->willReturn(2024);

        // Test boundary cases for 2024 season
        // SENIOR_3 (born before 1965-01-01)
        $birthdate = new \DateTimeImmutable('1964-12-31');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::SENIOR_3, $ageCategory);

        // SENIOR_2 boundary test (born exactly on 1965-01-01)
        // With inclusive comparison (>=), 1965-01-01 is >= 1965-01-01, so it's SENIOR_2
        $birthdate = new \DateTimeImmutable('1965-01-01');
        $ageCategory = $this->licenseHelper->ageCategoryForBirthdate($birthdate);
        $this->assertSame(LicenseAgeCategoryType::SENIOR_2, $ageCategory);
    }

    public function testLicenseCategoryTypeForBirthdate(): void
    {
        $this->seasonHelper
            ->method('getSelectedSeason')
            ->willReturn(2025);

        // Test Poussins (U11)
        $birthdate = new \DateTimeImmutable('2016-06-15');
        $categoryType = $this->licenseHelper->licenseCategoryTypeForBirthdate($birthdate);
        $this->assertSame(LicenseCategoryType::POUSSINS, $categoryType);

        // Test Jeunes (U13-U21)
        $birthdate = new \DateTimeImmutable('2006-06-15'); // U21
        $categoryType = $this->licenseHelper->licenseCategoryTypeForBirthdate($birthdate);
        $this->assertSame(LicenseCategoryType::JEUNES, $categoryType);

        // Test Adultes (SENIOR_1-3)
        $birthdate = new \DateTimeImmutable('1990-06-15'); // SENIOR_1
        $categoryType = $this->licenseHelper->licenseCategoryTypeForBirthdate($birthdate);
        $this->assertSame(LicenseCategoryType::ADULTES, $categoryType);
    }

    public function testAgeCategoryForBirthdateThrowsExceptionForFutureDate(): void
    {
        $this->seasonHelper
            ->method('getSelectedSeason')
            ->willReturn(2025);

        // Test with a future birthdate (should throw exception)
        $birthdate = new \DateTimeImmutable('2030-01-01');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Birthdate cannot be in the future. 2030-01-01 given');

        $this->licenseHelper->ageCategoryForBirthdate($birthdate);
    }

    // Note: The mapping is designed to handle all reasonable past birthdates.
    // Future dates are rejected with validation, past dates should always find a category.
    // This is by design - every person should have an age category.

    #[DataProvider('seasonDateProvider')]
    public function testGetSeasonForDateVariousDates(string $dateString, int $expectedSeason): void
    {
        $date = new \DateTimeImmutable($dateString);
        $season = LicenseHelper::getSeasonForDate($date);
        $this->assertSame($expectedSeason, $season);
    }

    public static function seasonDateProvider(): \Iterator
    {
        yield ['2023-01-01', 2023];
        yield ['2023-08-31', 2023];
        yield ['2023-09-01', 2024];
        yield ['2023-12-31', 2024];
        yield ['2024-06-15', 2024];
        yield ['2024-09-15', 2025];
    }
}
