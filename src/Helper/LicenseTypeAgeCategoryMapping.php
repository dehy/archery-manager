<?php

declare(strict_types=1);

namespace App\Helper;

use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;

/**
 * Maps license age categories to categories and license types according to FFTA rules.
 *
 * FFTA Structure (current):
 * - Age Category U11 → Category Poussin
 * - Age Categories U13, U15, U18, U21 → Category Jeunes
 * - Age Categories Senior 1, Senior 2, Senior 3 → Category Adultes
 *
 * Category determines available License Types:
 * - Poussin → License Type "Poussin"
 * - Jeunes → License Type "Jeune"
 * - Adultes → License Types "Adulte (compétition)", "Adulte (club)", "Adulte (sans pratique)"
 */
class LicenseTypeAgeCategoryMapping
{
    /**
     * Returns the category for a given age category.
     */
    public static function getCategoryForAgeCategory(string $ageCategory): ?string
    {
        return match ($ageCategory) {
            LicenseAgeCategoryType::U11 => LicenseCategoryType::POUSSINS,
            LicenseAgeCategoryType::U13,
            LicenseAgeCategoryType::U15,
            LicenseAgeCategoryType::U18,
            LicenseAgeCategoryType::U21 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::SENIOR_1,
            LicenseAgeCategoryType::SENIOR_2,
            LicenseAgeCategoryType::SENIOR_3 => LicenseCategoryType::ADULTES,
            // Deprecated age categories (no automatic mapping)
            default => null,
        };
    }

    /**
     * Returns the valid age categories for a given category.
     *
     * @return array<string> Array of valid age category constants
     */
    public static function getAgeCategoriesForCategory(string $category): array
    {
        return match ($category) {
            LicenseCategoryType::POUSSINS => [LicenseAgeCategoryType::U11],
            LicenseCategoryType::JEUNES => [
                LicenseAgeCategoryType::U13,
                LicenseAgeCategoryType::U15,
                LicenseAgeCategoryType::U18,
                LicenseAgeCategoryType::U21,
            ],
            LicenseCategoryType::ADULTES => [
                LicenseAgeCategoryType::SENIOR_1,
                LicenseAgeCategoryType::SENIOR_2,
                LicenseAgeCategoryType::SENIOR_3,
            ],
            default => [],
        };
    }

    /**
     * Returns the valid license types for a given category.
     *
     * @return array<string> Array of valid license type constants
     */
    public static function getLicenseTypesForCategory(string $category): array
    {
        return match ($category) {
            LicenseCategoryType::POUSSINS => [LicenseType::POUSSINS],
            LicenseCategoryType::JEUNES => [LicenseType::JEUNES],
            LicenseCategoryType::ADULTES => [
                LicenseType::ADULTES_COMPETITION,
                LicenseType::ADULTES_CLUB,
                LicenseType::ADULTES_SANS_PRATIQUE,
            ],
            default => [],
        };
    }

    /**
     * Returns all age category to category mappings.
     *
     * @return array<string, string> Key: age category value, Value: category value
     */
    public static function getAllAgeCategoryMappings(): array
    {
        return [
            LicenseAgeCategoryType::U11 => LicenseCategoryType::POUSSINS,
            LicenseAgeCategoryType::U13 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::U15 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::U18 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::U21 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::SENIOR_1 => LicenseCategoryType::ADULTES,
            LicenseAgeCategoryType::SENIOR_2 => LicenseCategoryType::ADULTES,
            LicenseAgeCategoryType::SENIOR_3 => LicenseCategoryType::ADULTES,
        ];
    }

    /**
     * Checks if an age category and category combination is valid.
     */
    public static function isValidAgeCategoryForCategory(string $ageCategory, string $category): bool
    {
        $validAgeCategories = self::getAgeCategoriesForCategory($category);

        return \in_array($ageCategory, $validAgeCategories, true);
    }

    /**
     * Checks if a license type and category combination is valid.
     */
    public static function isValidLicenseTypeForCategory(string $licenseType, string $category): bool
    {
        $validTypes = self::getLicenseTypesForCategory($category);

        return \in_array($licenseType, $validTypes, true);
    }

    /**
     * Get human-readable label for age category.
     */
    public static function getAgeCategoryLabel(string $ageCategory): string
    {
        return LicenseAgeCategoryType::getReadableValue($ageCategory);
    }

    /**
     * Get human-readable label for category.
     */
    public static function getCategoryLabel(string $category): string
    {
        return LicenseCategoryType::getReadableValue($category);
    }

    /**
     * Get human-readable label for license type.
     */
    public static function getLicenseTypeLabel(string $licenseType): string
    {
        return LicenseType::getReadableValue($licenseType);
    }
}
