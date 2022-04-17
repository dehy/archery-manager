<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class FletchingType extends AbstractEnumType
{
    public const PLASTIC = "plastic";
    public const SPINWINGS = "spinwings";

    protected static array $choices = [
        self::PLASTIC => "Plastique",
        self::SPINWINGS => "Spinwings",
    ];
}
