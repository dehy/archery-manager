<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class EventType extends AbstractEnumType
{
    public const TRAINING = "training";
    public const CONTEST_OFFICIAL = "contest_official";
    public const CONTEST_HOBBY = "contest_hobby";
    public const OTHER = "other";

    protected static array $choices = [
        self::TRAINING => "Entraînement",
        self::CONTEST_OFFICIAL => "Compétition Officielle",
        self::CONTEST_HOBBY => "Compétition Loisir",
        self::OTHER => "Autre",
    ];
}
