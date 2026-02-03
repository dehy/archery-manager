<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Helper\LicenseTypeAgeCategoryMapping;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LicenseTypeAgeCategoryMapping::class)]
final class LicenseTypeAgeCategoryMappingTest extends TestCase
{
    public function testGetCategoryForAgeCategoryU11ReturnsPoussins(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryForAgeCategory(LicenseAgeCategoryType::U11);

        $this->assertSame(LicenseCategoryType::POUSSINS, $result);
    }

    public function testGetCategoryForAgeCategoryU13ReturnsJeunes(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryForAgeCategory(LicenseAgeCategoryType::U13);

        $this->assertSame(LicenseCategoryType::JEUNES, $result);
    }

    public function testGetCategoryForAgeCategoryU15ReturnsJeunes(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryForAgeCategory(LicenseAgeCategoryType::U15);

        $this->assertSame(LicenseCategoryType::JEUNES, $result);
    }

    public function testGetCategoryForAgeCategoryU18ReturnsJeunes(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryForAgeCategory(LicenseAgeCategoryType::U18);

        $this->assertSame(LicenseCategoryType::JEUNES, $result);
    }

    public function testGetCategoryForAgeCategoryU21ReturnsJeunes(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryForAgeCategory(LicenseAgeCategoryType::U21);

        $this->assertSame(LicenseCategoryType::JEUNES, $result);
    }

    public function testGetCategoryForAgeCategorySenior1ReturnsAdultes(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryForAgeCategory(LicenseAgeCategoryType::SENIOR_1);

        $this->assertSame(LicenseCategoryType::ADULTES, $result);
    }

    public function testGetCategoryForAgeCategorySenior2ReturnsAdultes(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryForAgeCategory(LicenseAgeCategoryType::SENIOR_2);

        $this->assertSame(LicenseCategoryType::ADULTES, $result);
    }

    public function testGetCategoryForAgeCategorySenior3ReturnsAdultes(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryForAgeCategory(LicenseAgeCategoryType::SENIOR_3);

        $this->assertSame(LicenseCategoryType::ADULTES, $result);
    }

    public function testGetCategoryForAgeCategoryInvalidReturnsNull(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryForAgeCategory('invalid_category');

        $this->assertNull($result);
    }

    public function testGetAgeCategoriesForCategoryPoussinsReturnsU11(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getAgeCategoriesForCategory(LicenseCategoryType::POUSSINS);

        $this->assertSame([LicenseAgeCategoryType::U11], $result);
    }

    public function testGetAgeCategoriesForCategoryJeunesReturnsAllYouthCategories(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getAgeCategoriesForCategory(LicenseCategoryType::JEUNES);

        $expected = [
            LicenseAgeCategoryType::U13,
            LicenseAgeCategoryType::U15,
            LicenseAgeCategoryType::U18,
            LicenseAgeCategoryType::U21,
        ];
        $this->assertSame($expected, $result);
    }

    public function testGetAgeCategoriesForCategoryAdultesReturnsAllSeniorCategories(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getAgeCategoriesForCategory(LicenseCategoryType::ADULTES);

        $expected = [
            LicenseAgeCategoryType::SENIOR_1,
            LicenseAgeCategoryType::SENIOR_2,
            LicenseAgeCategoryType::SENIOR_3,
        ];
        $this->assertSame($expected, $result);
    }

    public function testGetAgeCategoriesForCategoryInvalidReturnsEmptyArray(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getAgeCategoriesForCategory('invalid_category');

        $this->assertSame([], $result);
    }

    public function testGetLicenseTypesForCategoryPoussinsReturnsPoussinsType(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getLicenseTypesForCategory(LicenseCategoryType::POUSSINS);

        $this->assertSame([LicenseType::POUSSINS], $result);
    }

    public function testGetLicenseTypesForCategoryJeunesReturnsJeunesType(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getLicenseTypesForCategory(LicenseCategoryType::JEUNES);

        $this->assertSame([LicenseType::JEUNES], $result);
    }

    public function testGetLicenseTypesForCategoryAdultesReturnsAllAdultTypes(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getLicenseTypesForCategory(LicenseCategoryType::ADULTES);

        $expected = [
            LicenseType::ADULTES_COMPETITION,
            LicenseType::ADULTES_CLUB,
            LicenseType::ADULTES_SANS_PRATIQUE,
        ];
        $this->assertSame($expected, $result);
    }

    public function testGetLicenseTypesForCategoryInvalidReturnsEmptyArray(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getLicenseTypesForCategory('invalid_category');

        $this->assertSame([], $result);
    }

    public function testGetAllAgeCategoryMappingsReturnsCompleteMapping(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getAllAgeCategoryMappings();

        $expected = [
            LicenseAgeCategoryType::U11 => LicenseCategoryType::POUSSINS,
            LicenseAgeCategoryType::U13 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::U15 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::U18 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::U21 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::SENIOR_1 => LicenseCategoryType::ADULTES,
            LicenseAgeCategoryType::SENIOR_2 => LicenseCategoryType::ADULTES,
            LicenseAgeCategoryType::SENIOR_3 => LicenseCategoryType::ADULTES,
        ];
        $this->assertSame($expected, $result);
    }

    public function testIsValidAgeCategoryForCategoryReturnsTrueForValidCombinations(): void
    {
        $this->assertTrue(
            LicenseTypeAgeCategoryMapping::isValidAgeCategoryForCategory(
                LicenseAgeCategoryType::U11,
                LicenseCategoryType::POUSSINS
            )
        );

        $this->assertTrue(
            LicenseTypeAgeCategoryMapping::isValidAgeCategoryForCategory(
                LicenseAgeCategoryType::U13,
                LicenseCategoryType::JEUNES
            )
        );

        $this->assertTrue(
            LicenseTypeAgeCategoryMapping::isValidAgeCategoryForCategory(
                LicenseAgeCategoryType::SENIOR_1,
                LicenseCategoryType::ADULTES
            )
        );
    }

    public function testIsValidAgeCategoryForCategoryReturnsFalseForInvalidCombinations(): void
    {
        $this->assertFalse(
            LicenseTypeAgeCategoryMapping::isValidAgeCategoryForCategory(
                LicenseAgeCategoryType::U11,
                LicenseCategoryType::JEUNES
            )
        );

        $this->assertFalse(
            LicenseTypeAgeCategoryMapping::isValidAgeCategoryForCategory(
                LicenseAgeCategoryType::U13,
                LicenseCategoryType::POUSSINS
            )
        );

        $this->assertFalse(
            LicenseTypeAgeCategoryMapping::isValidAgeCategoryForCategory(
                LicenseAgeCategoryType::SENIOR_1,
                LicenseCategoryType::JEUNES
            )
        );
    }

    public function testIsValidLicenseTypeForCategoryReturnsTrueForValidCombinations(): void
    {
        $this->assertTrue(
            LicenseTypeAgeCategoryMapping::isValidLicenseTypeForCategory(
                LicenseType::POUSSINS,
                LicenseCategoryType::POUSSINS
            )
        );

        $this->assertTrue(
            LicenseTypeAgeCategoryMapping::isValidLicenseTypeForCategory(
                LicenseType::JEUNES,
                LicenseCategoryType::JEUNES
            )
        );

        $this->assertTrue(
            LicenseTypeAgeCategoryMapping::isValidLicenseTypeForCategory(
                LicenseType::ADULTES_COMPETITION,
                LicenseCategoryType::ADULTES
            )
        );

        $this->assertTrue(
            LicenseTypeAgeCategoryMapping::isValidLicenseTypeForCategory(
                LicenseType::ADULTES_CLUB,
                LicenseCategoryType::ADULTES
            )
        );
    }

    public function testIsValidLicenseTypeForCategoryReturnsFalseForInvalidCombinations(): void
    {
        $this->assertFalse(
            LicenseTypeAgeCategoryMapping::isValidLicenseTypeForCategory(
                LicenseType::POUSSINS,
                LicenseCategoryType::JEUNES
            )
        );

        $this->assertFalse(
            LicenseTypeAgeCategoryMapping::isValidLicenseTypeForCategory(
                LicenseType::JEUNES,
                LicenseCategoryType::ADULTES
            )
        );

        $this->assertFalse(
            LicenseTypeAgeCategoryMapping::isValidLicenseTypeForCategory(
                LicenseType::ADULTES_COMPETITION,
                LicenseCategoryType::POUSSINS
            )
        );
    }

    public function testGetAgeCategoryLabelReturnsReadableValue(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getAgeCategoryLabel(LicenseAgeCategoryType::U11);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetCategoryLabelReturnsReadableValue(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getCategoryLabel(LicenseCategoryType::POUSSINS);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetLicenseTypeLabelReturnsReadableValue(): void
    {
        $result = LicenseTypeAgeCategoryMapping::getLicenseTypeLabel(LicenseType::ADULTES_COMPETITION);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
