<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class DisciplineType extends AbstractEnumType
{
    public const string TARGET = 'target';

    public const string INDOOR = 'indoor';

    public const string FIELD = 'field';

    public const string NATURE = 'nature';

    public const string THREE_D = '3d';

    public const string PARA = 'para';

    public const string RUN = 'run';

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
            default => throw new \LogicException(\sprintf('Unknown value "%s"', $extranetName)),
        };
    }
}
