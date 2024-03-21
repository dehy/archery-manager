<?php

namespace App\Factory;

use App\DBAL\Types\GenderType;
use App\Entity\Licensee;
use App\Repository\LicenseeRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Licensee>
 *
 * @method        Licensee|Proxy                     create(array|callable $attributes = [])
 * @method static Licensee|Proxy                     createOne(array $attributes = [])
 * @method static Licensee|Proxy                     find(object|array|mixed $criteria)
 * @method static Licensee|Proxy                     findOrCreate(array $attributes)
 * @method static Licensee|Proxy                     first(string $sortedField = 'id')
 * @method static Licensee|Proxy                     last(string $sortedField = 'id')
 * @method static Licensee|Proxy                     random(array $attributes = [])
 * @method static Licensee|Proxy                     randomOrCreate(array $attributes = [])
 * @method static LicenseeRepository|RepositoryProxy repository()
 * @method static Licensee[]|Proxy[]                 all()
 * @method static Licensee[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Licensee[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Licensee[]|Proxy[]                 findBy(array $attributes)
 * @method static Licensee[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Licensee[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<Licensee> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<Licensee> createOne(array $attributes = [])
 * @phpstan-method static Proxy<Licensee> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<Licensee> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<Licensee> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<Licensee> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<Licensee> random(array $attributes = [])
 * @phpstan-method static Proxy<Licensee> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<Licensee> repository()
 * @phpstan-method static list<Proxy<Licensee>> all()
 * @phpstan-method static list<Proxy<Licensee>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<Licensee>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<Licensee>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<Licensee>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<Licensee>> randomSet(int $number, array $attributes = [])
 */
final class LicenseeFactory extends ModelFactory
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
            'birthdate' => self::faker()->dateTimeBetween('-50 years', '-10 years'),
            'firstname' => self::faker()->firstName(),
            'gender' => self::faker()->randomElement(GenderType::getChoices()),
            'lastname' => self::faker()->lastName(),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Licensee $licensee): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Licensee::class;
    }
}
