<?php
namespace App\DBAL\Types;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class EventType extends AbstractEnumType
{
    public final const TRAINING = 'training';
    public final const CONTEST_OFFICIAL = 'contest_official';
    public final const CONTEST_HOBBY = 'contest_hobby';
    public final const OTHER = 'other';

    protected static array $choices = [
        self::TRAINING => "Entraînement",
        self::CONTEST_OFFICIAL => "Compétition Officielle",
        self::CONTEST_HOBBY => "Compétition Loisir",
        self::OTHER => "Autre",
    ];
}