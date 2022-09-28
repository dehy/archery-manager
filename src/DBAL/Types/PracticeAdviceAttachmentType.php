<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class PracticeAdviceAttachmentType extends AbstractEnumType
{
    public const MISC = 'misc';

    protected static array $choices = [
        self::MISC => 'Autre',
    ];
}
