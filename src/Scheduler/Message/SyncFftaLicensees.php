<?php

declare(strict_types=1);

namespace App\Scheduler\Message;

class SyncFftaLicensees
{
    public function __construct(
        private readonly int $id,
        private readonly string $clubCode,
        private readonly int $season
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getClubCode(): string
    {
        return $this->clubCode;
    }

    public function getSeason(): int
    {
        return $this->season;
    }
}
