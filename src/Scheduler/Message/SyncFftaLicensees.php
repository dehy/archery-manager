<?php

namespace App\Scheduler\Message;

class SyncFftaLicensees
{
    public function __construct(
        private readonly int $id,
        private readonly int $clubCode,
        private readonly int $season
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getClubCode(): int
    {
        return $this->clubCode;
    }

    public function getSeason(): int
    {
        return $this->season;
    }
}
