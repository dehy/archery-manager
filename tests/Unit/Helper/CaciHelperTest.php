<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Helper\CaciHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CaciHelperTest extends TestCase
{
    private CaciHelper $caciHelper;

    protected function setUp(): void
    {
        $this->caciHelper = new CaciHelper();
    }

    public function testGetMostRecentCertificateReturnsNullWhenNoAttachments(): void
    {
        $licensee = new Licensee();
        $result = $this->caciHelper->getMostRecentCertificate($licensee);

        $this->assertNull($result);
    }

    public function testGetMostRecentCertificateReturnsNullWhenNoMedicalCertificates(): void
    {
        $licensee = new Licensee();
        $otherAttachment = new LicenseeAttachment();
        $otherAttachment->setType(LicenseeAttachmentType::MISC);

        $licensee->addAttachment($otherAttachment);

        $result = $this->caciHelper->getMostRecentCertificate($licensee);

        $this->assertNull($result);
    }

    public function testGetMostRecentCertificateReturnsSingleCertificate(): void
    {
        $licensee = new Licensee();
        $cert = new LicenseeAttachment();
        $cert->setType(LicenseeAttachmentType::MEDICAL_CERTIFICATE);
        $cert->setDocumentDate(new \DateTimeImmutable('2024-01-15'));

        $licensee->addAttachment($cert);

        $result = $this->caciHelper->getMostRecentCertificate($licensee);

        $this->assertSame($cert, $result);
    }

    public function testGetMostRecentCertificateReturnsLatestByDate(): void
    {
        $licensee = new Licensee();

        $cert1 = new LicenseeAttachment();
        $cert1->setType(LicenseeAttachmentType::MEDICAL_CERTIFICATE);
        $cert1->setDocumentDate(new \DateTimeImmutable('2024-01-15'));

        $cert2 = new LicenseeAttachment();
        $cert2->setType(LicenseeAttachmentType::MEDICAL_CERTIFICATE);
        $cert2->setDocumentDate(new \DateTimeImmutable('2024-03-20'));

        $cert3 = new LicenseeAttachment();
        $cert3->setType(LicenseeAttachmentType::MEDICAL_CERTIFICATE);
        $cert3->setDocumentDate(new \DateTimeImmutable('2024-02-10'));

        $licensee->addAttachment($cert1);
        $licensee->addAttachment($cert2);
        $licensee->addAttachment($cert3);

        $result = $this->caciHelper->getMostRecentCertificate($licensee);

        $this->assertSame($cert2, $result);
    }

    public function testGetMostRecentCertificateHandlesCertificateWithoutDate(): void
    {
        $licensee = new Licensee();

        $cert1 = new LicenseeAttachment();
        $cert1->setType(LicenseeAttachmentType::MEDICAL_CERTIFICATE);
        $cert1->setDocumentDate(new \DateTimeImmutable('2024-01-15'));

        $cert2 = new LicenseeAttachment();
        $cert2->setType(LicenseeAttachmentType::MEDICAL_CERTIFICATE);
        // No date set

        $licensee->addAttachment($cert1);
        $licensee->addAttachment($cert2);

        $result = $this->caciHelper->getMostRecentCertificate($licensee);

        $this->assertSame($cert1, $result);
    }

    public function testGetMostRecentCertificateFiltersMixedAttachmentTypes(): void
    {
        $licensee = new Licensee();

        $cert = new LicenseeAttachment();
        $cert->setType(LicenseeAttachmentType::MEDICAL_CERTIFICATE);
        $cert->setDocumentDate(new \DateTimeImmutable('2024-01-15'));

        $otherAttachment = new LicenseeAttachment();
        $otherAttachment->setType(LicenseeAttachmentType::MISC);
        $otherAttachment->setDocumentDate(new \DateTimeImmutable('2024-03-20'));

        $licensee->addAttachment($cert);
        $licensee->addAttachment($otherAttachment);

        $result = $this->caciHelper->getMostRecentCertificate($licensee);

        $this->assertSame($cert, $result);
    }
}
