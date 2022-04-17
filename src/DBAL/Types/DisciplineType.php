<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class DisciplineType extends AbstractEnumType
{
    public const TARGET = "target";
    public const INDOOR = "indoor";
    public const FIELD = "field";
    public const NATURE = "nature";
    public const THREE_D = "3d";
    public const PARA = "para";
    public const RUN = "run";

    protected static array $choices = [
        self::TARGET => "Extérieur",
        self::INDOOR => "Salle",
        self::FIELD => "Campagne",
        self::NATURE => "Nature",
        self::THREE_D => "3D",
        self::PARA => "Handi",
        self::RUN => "Run Archery",
    ];
}
