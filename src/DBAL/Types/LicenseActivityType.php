<?php

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseActivityType extends AbstractEnumType
{
    public const AC = 'AC';
    public const AD = 'AD';
    public const BB = 'BB';
    public const CL = 'CL';
    public const CO = 'CO';
    public const TL = 'TL';

    protected static array $choices = [
        self::AC => 'Chasse',
        self::AD => 'Droit',
        self::BB => 'Bare bow',
        self::CL => 'Classique',
        self::CO => 'Poulies',
        self::TL => 'Libre',
    ];
}
