<?php

declare(strict_types=1);

namespace App\Scrapper;

/**
 * DTO representing a competition event scraped from the public FFTA website
 * (https://www.ffta.fr/competitions — no authentication required).
 */
final class FftaPublicEvent
{
    public function __construct(
        public readonly int $fftaEventId,
        public readonly string $fftaUrl,
        public readonly string $name,
        public readonly \DateTimeImmutable $startsAt,
        public readonly \DateTimeImmutable $endsAt,
        public readonly string $city,
        public readonly string $address,
        public readonly string $discipline,
        public readonly string $comiteDepartemental,
        public readonly string $comiteRegional,
        public readonly string $organizerName,
        public readonly ?string $contestType = null,
    ) {}
}
