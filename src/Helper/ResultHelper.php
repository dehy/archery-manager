<?php

declare(strict_types=1);

namespace App\Helper;

use App\DBAL\Types\LicenseAgeCategoryType;
use App\Entity\Result;

class ResultHelper
{
    /**
     * Results the way the results page lists them: by age category (youngest first), then activity, then
     * archer. The archer is compared by the name the caller is allowed to show.
     *
     * @param list<Result>                 $results
     * @param callable(Result): string     $archerName
     *
     * @return list<Result>
     */
    public static function sort(array $results, callable $archerName): array
    {
        $rankMap = array_flip(array_values(LicenseAgeCategoryType::getOrderedChoices()));
        usort($results, static function (Result $a, Result $b) use ($rankMap, $archerName): int {
            $rankA = $rankMap[$a->getAgeCategory()] ?? \PHP_INT_MAX;
            $rankB = $rankMap[$b->getAgeCategory()] ?? \PHP_INT_MAX;

            return [$rankA, $a->getActivity(), $archerName($a)] <=> [$rankB, $b->getActivity(), $archerName($b)];
        });

        return $results;
    }

    final public const string HEX_FORMAT = '#%02x%02x%02x';

    final public const string COLOR_LOWEST = '#FFDFD4';

    final public const string COLOR_BEST = '#E31D02';

    public static function colorRatio(float $ratio): string
    {
        [$c1r, $c1g, $c1b] = sscanf(self::COLOR_LOWEST, self::HEX_FORMAT);
        [$c2r, $c2g, $c2b] = sscanf(self::COLOR_BEST, self::HEX_FORMAT);

        // ratio 0 = c1r ; ratio 1 = c2r
        $cfr = round($c1r + ($c2r - $c1r) * $ratio);
        $cfg = round($c1g + ($c2g - $c1g) * $ratio);
        $cfb = round($c1b + ($c2b - $c1b) * $ratio);

        $cfr = min(255, max(0, $cfr));
        $cfg = min(255, max(0, $cfg));
        $cfb = min(255, max(0, $cfb));

        return \sprintf(self::HEX_FORMAT, $cfr, $cfg, $cfb);
    }
}
