<?php

namespace App\Entity;

class Season
{
    public static function seasonForDate(\DateTimeImmutable $date): int
    {
        $season = (int) ($date->format('Y'));
        if ((int) ($date->format('m')) >= 9) {
            ++$season;
        }

        return $season;
    }
}
