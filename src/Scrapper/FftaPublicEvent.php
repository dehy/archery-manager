<?php

declare(strict_types=1);

namespace App\Scrapper;

/**
 * DTO representing a competition event scraped from the public FFTA website
 * (https://www.ffta.fr/competitions — no authentication required).
 */
final readonly class FftaPublicEvent
{
    public function __construct(
        public int $fftaEventId,
        public string $fftaUrl,
        public string $name,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public string $city,
        public string $address,
        public string $discipline,
        public string $comiteDepartemental,
        public string $comiteRegional,
        public string $organizerName,
        public ?string $contestType = null,
    ) {
    }
}
