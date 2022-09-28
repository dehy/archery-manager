<?php

namespace App\Helper;

use App\DBAL\Types\LicenseCategoryType;
use App\Entity\Applicant;
use Money\Money;

class ApplicantHelper
{
    protected static int $season = 2023;
    protected static ?self $instance = null;

    public function __construct(protected LicenseHelper $licenseHelper)
    {
    }

    public static function default(): self
    {
        if (null === self::$instance) {
            self::$instance = new ApplicantHelper(new LicenseHelper());
        }

        return self::$instance;
    }

    public function licenseTypeForApplicant(Applicant $applicant): string
    {
        $tournament = $applicant->getTournament();

        return $this->licenseHelper->licenseTypeForBirthdate(
            $applicant->getBirthdate(),
            $tournament,
        );
    }

    public function licenseCategoryTypeForApplicant(
        Applicant $applicant,
    ): string {
        return $this->licenseHelper->licenseCategoryTypeForBirthdate(
            $applicant->getBirthdate(),
        );
    }

    public function licenseAgeCategoryForApplicant(
        Applicant $applicant,
    ): string {
        return $this->licenseHelper->ageCategoryForBirthdate(
            $applicant->getBirthdate(),
        );
    }

    public function toPayForApplicant(Applicant $applicant): Money
    {
        $isRenewal = $applicant->isRenewal();
        $birthdate = $applicant->getBirthdate();
        $licenseCategory = $this->licenseHelper->licenseCategoryTypeForBirthdate(
            $birthdate,
        );
        $isYoung = in_array($licenseCategory, [
            LicenseCategoryType::POUSSINS,
            LicenseCategoryType::JEUNES,
        ]);

        $isAdult = LicenseCategoryType::ADULTES === $licenseCategory;

        return match (true) {
            $isRenewal && $isYoung => Money::EUR('13000'),
            $isRenewal && $isAdult => Money::EUR('17000'),
            !$isRenewal && $isYoung => Money::EUR('15000'),
            !$isRenewal && $isAdult => Money::EUR('18000'),
        };
    }
}
