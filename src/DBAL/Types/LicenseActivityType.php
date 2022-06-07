<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class LicenseActivityType extends AbstractEnumType
{
    public const AC = "AC";
    public const AD = "AD";
    public const BB = "BB";
    public const CL = "CL";
    public const CO = "CO";
    public const TL = "TL";

    protected static array $choices = [
        self::AC => "Arc chasse",
        self::AD => "Arc droit",
        self::BB => "Bare bow",
        self::CL => "Arc Classique",
        self::CO => "Arc à poulies",
        self::TL => "Arc libre",
    ];
}
