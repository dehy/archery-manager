<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class EventAttachmentType extends AbstractEnumType
{
    public const string MANDATE = 'mandate';

    public const string RESULTS = 'results';

    public const string MISC = 'misc';

    protected static array $choices = [
        self::MANDATE => 'Mandat',
        self::RESULTS => 'Résultats',
        self::MISC => 'Autre',
    ];

    public static function icon($value): string
    {
        return match ($value) {
            self::MANDATE => 'fa-solid fa-bullhorn',
            self::RESULTS => 'fa-solid fa-medal',
            default => 'fa-solid fa-file',
        };
    }
}
