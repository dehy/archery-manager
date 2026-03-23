<?php

declare(strict_types=1);

namespace App\Helper;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;

readonly class CaciHelper
{
    /**
     * Finds the most recent medical certificate for a licensee.
     *
     * Iterates through all attachments of type MEDICAL_CERTIFICATE and returns
     * the one with the most recent documentDate. If no document date is set,
     * returns null (certificate is considered "unknown" status).
     */
    public function getMostRecentCertificate(Licensee $licensee): ?LicenseeAttachment
    {
        $certificate = null;
        foreach ($licensee->getAttachments() as $attachment) {
            if (LicenseeAttachmentType::MEDICAL_CERTIFICATE !== $attachment->getType()) {
                continue;
            }

            if (
                !$certificate instanceof LicenseeAttachment
                || ($attachment->getDocumentDate() instanceof \DateTimeImmutable
                    && $attachment->getDocumentDate() > $certificate->getDocumentDate())
            ) {
                $certificate = $attachment;
            }
        }

        return $certificate;
    }
}
