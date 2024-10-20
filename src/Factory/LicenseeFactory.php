<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Licensee;
use App\Scrapper\FftaProfile;

class LicenseeFactory
{
    public static function createFromFftaProfile(
        FftaProfile $profile,
    ): Licensee {
        return (new Licensee())
            ->setGender($profile->getSexe())
            ->setLastname($profile->getNom())
            ->setFirstname($profile->getPrenom())
            ->setFftaId($profile->getId())
            ->setFftaMemberCode($profile->getCodeAdherent())
            ->setBirthdate($profile->getDateNaissance());
    }
}
