<?php

namespace App\DBAL\Types;

use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\HobbyContestEvent;
use App\Entity\TrainingEvent;
use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class EventType extends AbstractEnumType
{
    public const TRAINING = TrainingEvent::class;
    public const CONTEST_OFFICIAL = ContestEvent::class;
    public const CONTEST_HOBBY = HobbyContestEvent::class;
    public const OTHER = Event::class;

    protected static array $choices = [
        self::TRAINING => 'Entraînement',
        self::CONTEST_OFFICIAL => 'Concours',
        self::CONTEST_HOBBY => 'Concours Loisir',
        self::OTHER => 'Autre',
    ];

    public static function colorCode(Event $event): string
    {
        return match ($event::class) {
            self::TRAINING => '#00CC00',
            self::CONTEST_OFFICIAL => '#CC0000',
            self::CONTEST_HOBBY => '#0000CC',
            default => '#CCCCCC',
        };
    }
}
