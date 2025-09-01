<?php

declare(strict_types=1);

namespace App\Helper;

use App\DBAL\Types\EventType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\HobbyContestEvent;
use App\Entity\License;

class LicenseHelper
{
    protected array $mappingSeason = [
        2023 => [
            '<1964-01-01' => LicenseAgeCategoryType::SENIOR_3,
            '>=1964-01-01_<=1983-12-31' => LicenseAgeCategoryType::SENIOR_2,
            '>=1984-01-01_<=2002-12-31' => LicenseAgeCategoryType::SENIOR_1,
            '>=2003-01-01_<=2005-12-31' => LicenseAgeCategoryType::U21,
            '>=2006-01-01_<=2008-12-31' => LicenseAgeCategoryType::U18,
            '>=2009-01-01_<=2010-12-31' => LicenseAgeCategoryType::U15,
            '>=2011-01-01_<=2012-12-31' => LicenseAgeCategoryType::U13,
            '>=2013-01-01' => LicenseAgeCategoryType::U11,
        ],
        2024 => [
            '<1965-01-01' => LicenseAgeCategoryType::SENIOR_3,
            '>=1965-01-01_<=1984-12-31' => LicenseAgeCategoryType::SENIOR_2,
            '>=1985-01-01_<=2003-12-31' => LicenseAgeCategoryType::SENIOR_1,
            '>=2004-01-01_<=2006-12-31' => LicenseAgeCategoryType::U21,
            '>=2007-01-01_<=2009-12-31' => LicenseAgeCategoryType::U18,
            '>=2010-01-01_<=2011-12-31' => LicenseAgeCategoryType::U15,
            '>=2012-01-01_<=2013-12-31' => LicenseAgeCategoryType::U13,
            '>=2014-01-01' => LicenseAgeCategoryType::U11,
        ],
        2025 => [
            '<1966-01-01' => LicenseAgeCategoryType::SENIOR_3,
            '>=1966-01-01_<=1985-12-31' => LicenseAgeCategoryType::SENIOR_2,
            '>=1986-01-01_<=2004-12-31' => LicenseAgeCategoryType::SENIOR_1,
            '>=2005-01-01_<=2007-12-31' => LicenseAgeCategoryType::U21,
            '>=2008-01-01_<=2010-12-31' => LicenseAgeCategoryType::U18,
            '>=2011-01-01_<=2012-12-31' => LicenseAgeCategoryType::U15,
            '>=2013-01-01_<=2014-12-31' => LicenseAgeCategoryType::U13,
            '>=2015-01-01' => LicenseAgeCategoryType::U11,
        ],
    ];

    public function __construct(
        private readonly LicenseeHelper $licenseeHelper,
        private readonly SeasonHelper $seasonHelper,
    ) {
    }

    public static function getSeasonForDate(\DateTimeInterface $dateTime): int
    {
        $dateYear = (int) $dateTime->format('Y');

        return $dateTime->format('n') >= 9 ? ($dateYear + 1) : $dateYear;
    }

    public function getCurrentLicenseeCurrentLicense(): ?License
    {
        $licensee = $this->licenseeHelper->getLicenseeFromSession();

        return $licensee?->getLicenseForSeason($this->seasonHelper->getSelectedSeason());
    }

    public function licenseIsValidForEvent(License $license, Event $event): bool
    {
        $licenseType = $license->getType();
        $isValid = false;
        if (ContestEvent::class === $event::class) {
            if (\in_array($licenseType, [LicenseType::ADULTES_COMPETITION, LicenseType::JEUNES])) {
                $isValid = true;
            }
        } elseif (HobbyContestEvent::class === $event::class) {
            if (\in_array($licenseType, [LicenseType::ADULTES_CLUB, LicenseType::JEUNES, LicenseType::POUSSINS])) {
                $isValid = true;
            }
        } elseif (EventType::TRAINING === $event::class) {
            $isValid = true;
        } elseif (EventType::FREE_TRAINING === $event::class) {
            $isValid = true;
        } elseif (EventType::OTHER === $event::class) {
            $isValid = true;
        }

        return $isValid;
    }

    public function licenseTypeForBirthdate(
        \DateTimeInterface $birthdate,
        bool $tournament,
    ): string {
        $categoryType = $this->licenseCategoryTypeForBirthdate($birthdate);

        return match (true) {
            LicenseCategoryType::ADULTES === $categoryType && $tournament => LicenseType::ADULTES_COMPETITION,
            LicenseCategoryType::ADULTES === $categoryType && !$tournament => LicenseType::ADULTES_CLUB,
            LicenseCategoryType::JEUNES === $categoryType => LicenseType::JEUNES,
            LicenseCategoryType::POUSSINS === $categoryType => LicenseType::POUSSINS,
        };
    }

    public function licenseCategoryTypeForBirthdate(
        \DateTimeInterface $birthdate,
    ): string {
        $ageCategory = $this->ageCategoryForBirthdate($birthdate);

        return $this->categoryTypeForAgeCategory($ageCategory);
    }

    public function categoryTypeForAgeCategory(string $ageCategory): string
    {
        LicenseAgeCategoryType::assertValidChoice($ageCategory);

        return match ($ageCategory) {
            LicenseAgeCategoryType::U11 => LicenseCategoryType::POUSSINS,
            LicenseAgeCategoryType::U13,
            LicenseAgeCategoryType::U15,
            LicenseAgeCategoryType::U18,
            LicenseAgeCategoryType::U21 => LicenseCategoryType::JEUNES,
            LicenseAgeCategoryType::SENIOR_1,
            LicenseAgeCategoryType::SENIOR_2,
            LicenseAgeCategoryType::SENIOR_3 => LicenseCategoryType::ADULTES,
        };
    }

    public function ageCategoryForBirthdate(
        \DateTimeInterface $birthdate,
    ): string {
        // Validate that birthdate is not in the future
        $today = new \DateTimeImmutable();
        if ($birthdate > $today) {
            throw new \LogicException(\sprintf('Birthdate cannot be in the future. %s given', $birthdate->format('Y-m-d')));
        }

        $mapping = $this->mappingSeason[$this->seasonHelper->getSelectedSeason()];

        foreach ($mapping as $dateKey => $ageCategory) {
            $parts = explode('_', (string) $dateKey);
            $leftPart = $parts[0];
            $rightPart = $parts[1] ?? null;
            $after = null;
            $before = null;
            $afterInclusive = false;
            $beforeInclusive = false;
            
            if (null === $rightPart || '' === $rightPart || '0' === $rightPart) {
                if (str_starts_with($leftPart, '>=')) {
                    $after = $this->dateTimeFromKeyPartInclusive($leftPart);
                    $afterInclusive = true;
                } elseif (str_starts_with($leftPart, '<=')) {
                    $before = $this->dateTimeFromKeyPartInclusive($leftPart);
                    $beforeInclusive = true;
                } elseif (str_starts_with($leftPart, '>')) {
                    $after = $this->dateTimeFromKeyPart($leftPart);
                    $afterInclusive = false;
                } elseif (str_starts_with($leftPart, '<')) {
                    $before = $this->dateTimeFromKeyPart($leftPart);
                    $beforeInclusive = false;
                }
            } else {
                // Handle left part
                if (str_starts_with($leftPart, '>=')) {
                    $after = $this->dateTimeFromKeyPartInclusive($leftPart);
                    $afterInclusive = true;
                } else {
                    $after = $this->dateTimeFromKeyPart($leftPart);
                    $afterInclusive = false;
                }
                
                // Handle right part  
                if (str_starts_with($rightPart, '<=')) {
                    $before = $this->dateTimeFromKeyPartInclusive($rightPart);
                    $beforeInclusive = true;
                } else {
                    $before = $this->dateTimeFromKeyPart($rightPart);
                    $beforeInclusive = false;
                }
            }

            $afterCheck = ($after === null) || 
                         ($afterInclusive ? $birthdate >= $after : $birthdate > $after);
            $beforeCheck = ($before === null) || 
                          ($beforeInclusive ? $birthdate <= $before : $birthdate < $before);

            if ($afterCheck && $beforeCheck) {
                return $ageCategory;
            }
        }

        throw new \LogicException(\sprintf('Should have found a value. %s given', $birthdate->format('Y-m-d')));
    }

    private function dateTimeFromKeyPart(string $keyPart): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            substr($keyPart, 1, 10),
        );
        // Set time to start of day to avoid time comparison issues
        return $date->setTime(0, 0, 0);
    }

    private function dateTimeFromKeyPartInclusive(string $keyPart): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            substr($keyPart, 2, 10), // Skip ">=" or "<="
        );
        // Set time to start of day to avoid time comparison issues
        return $date->setTime(0, 0, 0);
    }
}
