<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class LicenseActivityType extends AbstractEnumType
{
    public const string AC = 'AC';

    public const string AD = 'AD';

    public const string BB = 'BB';

    public const string CL = 'CL';

    public const string CO = 'CO';

    public const string TL = 'TL';

    protected static array $choices = [
        self::AC => 'Chasse',
        self::AD => 'Droit',
        self::BB => 'Bare bow',
        self::CL => 'Classique',
        self::CO => 'Poulies',
        self::TL => 'Libre',
    ];
}
