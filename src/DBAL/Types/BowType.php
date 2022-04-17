<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class BowType extends AbstractEnumType
{
    public const INITIATION = "initiation";
    public const CLASSIQUE_COMPETITION = "classique_competition";
    public const POULIES = "poulies";
    public const BAREBOW = "barebow";
    public const LONGBOW = "longbow";

    protected static array $choices = [
        self::INITIATION => "Initiation",
        self::CLASSIQUE_COMPETITION => "Classique compétition",
        self::POULIES => "Poulies",
        self::BAREBOW => "Barebow",
        self::LONGBOW => "Longbow",
    ];
}
