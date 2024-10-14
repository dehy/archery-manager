<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class PracticeLevelType extends AbstractEnumType
{
    public const string BEGINNER = 'beginner';

    public const string INTERMEDIATE = 'intermediate';

    public const string ADVANCED = 'advanced';

    protected static array $choices = [
        self::BEGINNER => 'Débutant',
        self::INTERMEDIATE => 'Intermédiaire',
        self::ADVANCED => 'Avancé',
    ];
}
