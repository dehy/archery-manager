<?php

namespace App\Factory;

use App\DBAL\Types\DisciplineType;
use App\Entity\Event;
use App\Entity\EventRecurringPattern;
use App\Repository\EventRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Event>
 *
 * @method        Event|Proxy                     create(array|callable $attributes = [])
 * @method static Event|Proxy                     createOne(array $attributes = [])
 * @method static Event|Proxy                     find(object|array|mixed $criteria)
 * @method static Event|Proxy                     findOrCreate(array $attributes)
 * @method static Event|Proxy                     first(string $sortedField = 'id')
 * @method static Event|Proxy                     last(string $sortedField = 'id')
 * @method static Event|Proxy                     random(array $attributes = [])
 * @method static Event|Proxy                     randomOrCreate(array $attributes = [])
 * @method static EventRepository|RepositoryProxy repository()
 * @method static Event[]|Proxy[]                 all()
 * @method static Event[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Event[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Event[]|Proxy[]                 findBy(array $attributes)
 * @method static Event[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Event[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<Event> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<Event> createOne(array $attributes = [])
 * @phpstan-method static Proxy<Event> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<Event> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<Event> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<Event> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<Event> random(array $attributes = [])
 * @phpstan-method static Proxy<Event> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<Event> repository()
 * @phpstan-method static list<Proxy<Event>> all()
 * @phpstan-method static list<Proxy<Event>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<Event>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<Event>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<Event>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<Event>> randomSet(int $number, array $attributes = [])
 */
final class EventFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function getDefaults(): array
    {
        return [
            'address' => self::faker()->address(),
            'createdAt' => new \DateTimeImmutable(),
            'createdBy' => UserFactory::randomOrCreate(),
            'description' => self::faker()->text(255),
            'discipline' => self::faker()->randomElement(DisciplineType::getChoices()),
            'fullDayEvent' => false,
            'name' => self::faker()->text(23),
            'recurring' => false,
            'slug' => self::faker()->text(255),
            'startDate' => new \DateTimeImmutable('2023-09-09T00:00:00Z'),
            'endDate' => new \DateTimeImmutable('2023-09-09T00:00:00Z'),
            'startTime' => new \DateTimeImmutable(date('Y-m-d').'T09:45:00Z'),
            'endTime' => new \DateTimeImmutable(date('Y-m-d').'T11:00:00Z'),
        ];
    }

    public function weeklyRecurrent(int $maxNumOfOccurrences = null): self
    {
        return $this->addState([
            'recurring' => true,
            'recurringPatterns' => [
                EventRecurringPatternFactory::new([
                    'maxNumOfOccurrences' => $maxNumOfOccurrences,
                ]),
            ],
        ]);
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Event $event): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Event::class;
    }
}
