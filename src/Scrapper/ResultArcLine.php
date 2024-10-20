<?php

declare(strict_types=1);

namespace App\Scrapper;

class ResultArcLine
{
    public function __construct(
        public string $fftaCode,
        public string $ageCategory,
        public string $activity,
        public int $score,
    ) {
    }
}
