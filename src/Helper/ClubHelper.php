<?php

namespace App\Helper;

use App\Entity\Club;
use App\Entity\Licensee;
use App\Entity\Season;

class ClubHelper
{
    public function __construct(private readonly LicenseHelper $licenseHelper)
    {
    }

    public function activeClub(): ?Club
    {
        return $this->licenseHelper->getCurrentLicenseeCurrentLicense()?->getClub();
    }

    public function activeClubFor(Licensee $licensee): ?Club
    {
        return $licensee->getLicenseForSeason(Season::seasonForDate(new \DateTimeImmutable()))?->getClub();
    }

    public function primaryColor(): string
    {
        return $this->activeClub()?->getPrimaryColor() ?? $this->defaultColor();
    }

    public function defaultColor(): string
    {
        return '#999999';
    }

}