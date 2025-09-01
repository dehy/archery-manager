<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\Applicant;
use App\Helper\ApplicantHelper;
use App\Helper\LicenseHelper;
use Money\Money;
use PHPUnit\Framework\TestCase;

final class ApplicantHelperTest extends TestCase
{
    private LicenseHelper $licenseHelper;

    private ApplicantHelper $applicantHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->licenseHelper = $this->createMock(LicenseHelper::class);
        $this->applicantHelper = new ApplicantHelper($this->licenseHelper);
    }

    public function testLicenseTypeForApplicantTournament(): void
    {
        $birthdate = new \DateTimeImmutable('1990-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('getTournament')->willReturn(true);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->expects($this->once())
            ->method('licenseTypeForBirthdate')
            ->with($birthdate, true)
            ->willReturn(LicenseType::ADULTES_COMPETITION);

        $result = $this->applicantHelper->licenseTypeForApplicant($applicant);

        $this->assertSame(LicenseType::ADULTES_COMPETITION, $result);
    }

    public function testLicenseTypeForApplicantNonTournament(): void
    {
        $birthdate = new \DateTimeImmutable('1990-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('getTournament')->willReturn(false);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->expects($this->once())
            ->method('licenseTypeForBirthdate')
            ->with($birthdate, false)
            ->willReturn(LicenseType::ADULTES_CLUB);

        $result = $this->applicantHelper->licenseTypeForApplicant($applicant);

        $this->assertSame(LicenseType::ADULTES_CLUB, $result);
    }

    public function testLicenseCategoryTypeForApplicant(): void
    {
        $birthdate = new \DateTimeImmutable('2015-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->expects($this->once())
            ->method('licenseCategoryTypeForBirthdate')
            ->with($birthdate)
            ->willReturn(LicenseCategoryType::POUSSINS);

        $result = $this->applicantHelper->licenseCategoryTypeForApplicant($applicant);

        $this->assertSame(LicenseCategoryType::POUSSINS, $result);
    }

    public function testLicenseAgeCategoryForApplicant(): void
    {
        $birthdate = new \DateTimeImmutable('2010-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->expects($this->once())
            ->method('ageCategoryForBirthdate')
            ->with($birthdate)
            ->willReturn(LicenseAgeCategoryType::U15);

        $result = $this->applicantHelper->licenseAgeCategoryForApplicant($applicant);

        $this->assertSame(LicenseAgeCategoryType::U15, $result);
    }

    public function testToPayForApplicantRenewalYoung(): void
    {
        $birthdate = new \DateTimeImmutable('2015-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('isRenewal')->willReturn(true);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->method('licenseCategoryTypeForBirthdate')
            ->with($birthdate)
            ->willReturn(LicenseCategoryType::POUSSINS);

        $result = $this->applicantHelper->toPayForApplicant($applicant);

        $this->assertTrue($result->equals(Money::EUR('13000')));
    }

    public function testToPayForApplicantRenewalAdult(): void
    {
        $birthdate = new \DateTimeImmutable('1990-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('isRenewal')->willReturn(true);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->method('licenseCategoryTypeForBirthdate')
            ->with($birthdate)
            ->willReturn(LicenseCategoryType::ADULTES);

        $result = $this->applicantHelper->toPayForApplicant($applicant);

        $this->assertTrue($result->equals(Money::EUR('17000')));
    }

    public function testToPayForApplicantNewYoung(): void
    {
        $birthdate = new \DateTimeImmutable('2010-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('isRenewal')->willReturn(false);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->method('licenseCategoryTypeForBirthdate')
            ->with($birthdate)
            ->willReturn(LicenseCategoryType::JEUNES);

        $result = $this->applicantHelper->toPayForApplicant($applicant);

        $this->assertTrue($result->equals(Money::EUR('15000')));
    }

    public function testToPayForApplicantNewAdult(): void
    {
        $birthdate = new \DateTimeImmutable('1990-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('isRenewal')->willReturn(false);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->method('licenseCategoryTypeForBirthdate')
            ->with($birthdate)
            ->willReturn(LicenseCategoryType::ADULTES);

        $result = $this->applicantHelper->toPayForApplicant($applicant);

        $this->assertTrue($result->equals(Money::EUR('18000')));
    }

    public function testToPayForApplicantRenewalJeunes(): void
    {
        $birthdate = new \DateTimeImmutable('2008-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('isRenewal')->willReturn(true);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->method('licenseCategoryTypeForBirthdate')
            ->with($birthdate)
            ->willReturn(LicenseCategoryType::JEUNES);

        $result = $this->applicantHelper->toPayForApplicant($applicant);

        $this->assertTrue($result->equals(Money::EUR('13000')));
    }

    public function testToPayForApplicantNewPoussins(): void
    {
        $birthdate = new \DateTimeImmutable('2016-06-15');

        $applicant = $this->createMock(Applicant::class);
        $applicant->method('isRenewal')->willReturn(false);
        $applicant->method('getBirthdate')->willReturn($birthdate);

        $this->licenseHelper
            ->method('licenseCategoryTypeForBirthdate')
            ->with($birthdate)
            ->willReturn(LicenseCategoryType::POUSSINS);

        $result = $this->applicantHelper->toPayForApplicant($applicant);

        $this->assertTrue($result->equals(Money::EUR('15000')));
    }
}
