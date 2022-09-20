<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class PracticeLevelType extends AbstractEnumType
{
    public const BEGINNER = 'beginner';
    public const INTERMEDIATE = 'intermediate';
    public const ADVANCED = 'advanced';

    protected static array $choices = [
        self::BEGINNER => 'Débutant',
        self::INTERMEDIATE => 'Intermédiaire',
        self::ADVANCED => 'Avancé',
    ];
}
