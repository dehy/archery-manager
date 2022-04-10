<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class BowType extends AbstractEnumType
{
    public final const INITIATION = 'initiation';
    public final const CLASSIQUE_COMPETITION = 'classique_competition';
    public final const POULIES = 'poulies';
    public final const BAREBOW = 'barebow';
    public final const LONGBOW = 'longbow';

    protected static array $choices = [
        self::INITIATION => "Initiation",
        self::CLASSIQUE_COMPETITION => "Classique compétition",
        self::POULIES => "Poulies",
        self::BAREBOW => "Barebow",
        self::LONGBOW => "Longbow",
    ];
}