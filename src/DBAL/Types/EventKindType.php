<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class EventKindType extends AbstractEnumType
{
    public const TRAINING = 'training';
    public const CONTEST_OFFICIAL = 'contest_official';
    public const CONTEST_HOBBY = 'contest_hobby';
    public const OTHER = 'other';

    protected static array $choices = [
        self::TRAINING => 'Entraînement',
        self::CONTEST_OFFICIAL => 'Concours',
        self::CONTEST_HOBBY => 'Concours Loisir',
        self::OTHER => 'Autre',
    ];

    public static function colorCode(string $choice): string
    {
        return match ($choice) {
            self::TRAINING => '#00CC00',
            self::CONTEST_OFFICIAL => '#CC0000',
            self::CONTEST_HOBBY => '#0000CC',
            default => '#CCCCCC',
        };
    }
}
