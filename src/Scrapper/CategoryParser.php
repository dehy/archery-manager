<?php

namespace App\Scrapper;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;

/**
 * Parse FFTA Categories
 */
class CategoryParser
{
    public static function parseString(string $categoryString): ?array
    {
        $ageCategories = implode("|", LicenseAgeCategoryType::getValues());
        $activityTypes = implode("|", LicenseActivityType::getValues());

        $matchPattern = sprintf("(%s)[HF](%s)", $ageCategories, $activityTypes);

        $re = "/" . $matchPattern . "/m";
        if (1 === preg_match($re, $categoryString, $matches)) {
            $ageCategory = $matches[1];
            $activity = $matches[2];
            return [$ageCategory, $activity];
        }

        return null;
    }
}
