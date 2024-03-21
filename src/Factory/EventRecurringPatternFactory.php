<?php

namespace App\Factory;

use App\DBAL\Types\RecurringType;
use App\Entity\EventRecurringPattern;
use App\Repository\EventRecurringPatternRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<EventRecurringPattern>
 *
 * @method        EventRecurringPattern|Proxy                     create(array|callable $attributes = [])
 * @method static EventRecurringPattern|Proxy                     createOne(array $attributes = [])
 * @method static EventRecurringPattern|Proxy                     find(object|array|mixed $criteria)
 * @method static EventRecurringPattern|Proxy                     findOrCreate(array $attributes)
 * @method static EventRecurringPattern|Proxy                     first(string $sortedField = 'id')
 * @method static EventRecurringPattern|Proxy                     last(string $sortedField = 'id')
 * @method static EventRecurringPattern|Proxy                     random(array $attributes = [])
 * @method static EventRecurringPattern|Proxy                     randomOrCreate(array $attributes = [])
 * @method static EventRecurringPatternRepository|RepositoryProxy repository()
 * @method static EventRecurringPattern[]|Proxy[]                 all()
 * @method static EventRecurringPattern[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static EventRecurringPattern[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static EventRecurringPattern[]|Proxy[]                 findBy(array $attributes)
 * @method static EventRecurringPattern[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static EventRecurringPattern[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<EventRecurringPattern> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<EventRecurringPattern> createOne(array $attributes = [])
 * @phpstan-method static Proxy<EventRecurringPattern> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<EventRecurringPattern> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<EventRecurringPattern> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<EventRecurringPattern> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<EventRecurringPattern> random(array $attributes = [])
 * @phpstan-method static Proxy<EventRecurringPattern> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<EventRecurringPattern> repository()
 * @phpstan-method static list<Proxy<EventRecurringPattern>> all()
 * @phpstan-method static list<Proxy<EventRecurringPattern>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<EventRecurringPattern>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<EventRecurringPattern>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<EventRecurringPattern>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<EventRecurringPattern>> randomSet(int $number, array $attributes = [])
 */
final class EventRecurringPatternFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'event' => EventFactory::new(),
            'recurringType' => RecurringType::WEEKLY,
            'separationCount' => 0,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(EventRecurringPattern $eventRecurringPattern): void {})
        ;
    }

    protected static function getClass(): string
    {
        return EventRecurringPattern::class;
    }
}
