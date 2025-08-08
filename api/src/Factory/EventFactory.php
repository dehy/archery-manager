<?php

namespace App\Factory;

use App\Entity\Event;
use App\Entity\PostalAddress;
use App\Type\EventStatusType;
use App\Type\SportType;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Event>
 */
final class EventFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Event::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        $startDate = self::faker()->dateTimeThisMonth();
        $endDate = (clone $startDate)->modify('+2 hour');

        return [
            'sport' => SportType::Archery,
            'organizer' => ClubFactory::random(),
            'name' => self::faker()->name(),
            'description' => self::faker()->text(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => EventStatusType::Scheduled,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Event $Event): void {})
        ;
    }
}
