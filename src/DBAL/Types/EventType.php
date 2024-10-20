<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\FreeTrainingEvent;
use App\Entity\HobbyContestEvent;
use App\Entity\TrainingEvent;
use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

/**
 * @extends AbstractEnumType<string, string>
 */
final class EventType extends AbstractEnumType
{
    public const string TRAINING = TrainingEvent::class;

    public const string FREE_TRAINING = FreeTrainingEvent::class;

    public const string CONTEST_OFFICIAL = ContestEvent::class;

    public const string CONTEST_HOBBY = HobbyContestEvent::class;

    public const string OTHER = Event::class;

    protected static array $choices = [
        self::TRAINING => 'Entraînement',
        self::FREE_TRAINING => 'Entraînement libre',
        self::CONTEST_OFFICIAL => 'Concours',
        self::CONTEST_HOBBY => 'Concours Loisir',
        self::OTHER => 'Autre',
    ];

    public static function colorCode(Event $event): string
    {
        return match ($event::class) {
            self::TRAINING => '#00A0FF',
            self::FREE_TRAINING => '#B3E3FF',
            self::CONTEST_OFFICIAL => '#FF6700',
            self::CONTEST_HOBBY => '#FFD2B4',
            default => '#808080',
        };
    }
}
