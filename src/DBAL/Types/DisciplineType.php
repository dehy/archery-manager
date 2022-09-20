<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;
use LogicException;

/**
 * @extends AbstractEnumType<string, string>
 */
final class DisciplineType extends AbstractEnumType
{
    public const TARGET = 'target';
    public const INDOOR = 'indoor';
    public const FIELD = 'field';
    public const NATURE = 'nature';
    public const THREE_D = '3d';
    public const PARA = 'para';
    public const RUN = 'run';

    protected static array $choices = [
        self::TARGET => 'Extérieur',
        self::INDOOR => 'Salle',
        self::FIELD => 'Campagne',
        self::NATURE => 'Nature',
        self::THREE_D => '3D',
        self::PARA => 'Handi',
        self::RUN => 'Run Archery',
    ];

    public static function disciplineFromFftaExtranet(
        string $extranetName,
    ): string {
        return match ($extranetName) {
            "Tir à l'Arc Extérieur" => self::TARGET,
            'Tir 3D' => self::THREE_D,
            'Tir Nature' => self::NATURE,
            'Tir en Salle' => self::INDOOR,
            default => throw new LogicException("Unknown value \"{$extranetName}\""),
        };
    }
}
