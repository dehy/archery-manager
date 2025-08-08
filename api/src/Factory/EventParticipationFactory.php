<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\EventParticipation;
use App\Type\EventParticipationStateType;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<EventParticipation>
 */
final class EventParticipationFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return EventParticipation::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'participationState' => EventParticipationStateType::Interested,
            'event' => EventFactory::new(),
            'participant' => LicenseeFactory::new(),
        ];
    }
}
