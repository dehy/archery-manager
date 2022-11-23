<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseeAttachmentType extends AbstractEnumType
{
    public const PROFILE_PICTURE = 'profile_picture';
    public const LICENSE_APPLICATION = 'license_application';
    public const MEDICAL_CERTIFICATE = 'medical_certificate';
    public const MISC = 'misc';

    protected static array $choices = [
        self::PROFILE_PICTURE => 'Photo de profil',
        self::LICENSE_APPLICATION => 'Demande de license',
        self::MEDICAL_CERTIFICATE => 'Certificat médical',
        self::MISC => 'Autre',
    ];
}
