<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseeAttachmentType extends AbstractEnumType
{
    public const string PROFILE_PICTURE = 'profile_picture';

    public const string LICENSE_APPLICATION = 'license_application';

    public const string MEDICAL_CERTIFICATE = 'medical_certificate';

    public const string MISC = 'misc';

    protected static array $choices = [
        self::PROFILE_PICTURE => 'Photo de profil',
        self::LICENSE_APPLICATION => 'Demande de license',
        self::MEDICAL_CERTIFICATE => 'Certificat médical',
        self::MISC => 'Autre',
    ];
}
