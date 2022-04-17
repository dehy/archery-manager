<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class ArrowType extends AbstractEnumType
{
    public const WOOD = "wood";
    public const ALUMINUM = "aluminum";
    public const CARBON = "carbon";
    public const ALUMINUM_CARBON = "aluminum_carbon";

    protected static array $choices = [
        self::WOOD => "Bois",
        self::ALUMINUM => "Aluminium",
        self::CARBON => "Carbone",
        self::ALUMINUM_CARBON => "Aluminium Carbone",
    ];
}
