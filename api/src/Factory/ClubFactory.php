<?php

namespace App\Factory;

use App\Entity\Club;
use App\Type\SportType;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Club>
 */
final class ClubFactory extends PersistentProxyObjectFactory
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
        return Club::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        $date = \DateTimeImmutable::createFromMutable(self::faker()->dateTime());

        return [
            'address' => PostalAddressFactory::new(),
            'email' => self::faker()->safeEmail(),
            'createdAt' => $date,
            'fftaCode' => self::faker()->unique()->numerify('1033###'),
            'name' => self::faker()->company(),
            'primaryColor' => self::faker()->hexColor(),
            'sport' => SportType::Archery,
            'updatedAt' => $date,
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Club $club): void {})
        ;
    }
}
