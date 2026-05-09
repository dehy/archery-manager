<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class PracticeAdviceAttachmentType extends AbstractEnumType
{
    public const string MISC = 'misc';

    #[\Override]
    protected static array $choices = [
        self::MISC => 'Autre',
    ];
}
