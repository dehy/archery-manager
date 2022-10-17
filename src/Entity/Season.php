<?php

namespace App\Entity;

use DateTimeImmutable;

class Season
{
    public static function seasonForDate(DateTimeImmutable $date): int
    {
        $season = intval($date->format('Y'));
        if (intval($date->format('m')) >= 9) {
            ++$season;
        }

        return $season;
    }
}
