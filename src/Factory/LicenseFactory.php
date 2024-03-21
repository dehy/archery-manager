<?php

namespace App\Factory;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseType;
use App\Entity\License;
use App\Repository\LicenseRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<License>
 *
 * @method        License|Proxy                     create(array|callable $attributes = [])
 * @method static License|Proxy                     createOne(array $attributes = [])
 * @method static License|Proxy                     find(object|array|mixed $criteria)
 * @method static License|Proxy                     findOrCreate(array $attributes)
 * @method static License|Proxy                     first(string $sortedField = 'id')
 * @method static License|Proxy                     last(string $sortedField = 'id')
 * @method static License|Proxy                     random(array $attributes = [])
 * @method static License|Proxy                     randomOrCreate(array $attributes = [])
 * @method static LicenseRepository|RepositoryProxy repository()
 * @method static License[]|Proxy[]                 all()
 * @method static License[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static License[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static License[]|Proxy[]                 findBy(array $attributes)
 * @method static License[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static License[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<License> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<License> createOne(array $attributes = [])
 * @phpstan-method static Proxy<License> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<License> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<License> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<License> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<License> random(array $attributes = [])
 * @phpstan-method static Proxy<License> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<License> repository()
 * @phpstan-method static list<Proxy<License>> all()
 * @phpstan-method static list<Proxy<License>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<License>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<License>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<License>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<License>> randomSet(int $number, array $attributes = [])
 */
final class LicenseFactory extends ModelFactory
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
            'activities' => [self::faker()->randomElement(LicenseActivityType::getChoices())],
            'club' => ClubFactory::new(),
            'licensee' => LicenseeFactory::new(),
            'season' => self::faker()->randomElement([2024]),
            'type' => self::faker()->randomElement(LicenseType::getChoices()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(License $license): void {})
        ;
    }

    protected static function getClass(): string
    {
        return License::class;
    }
}
