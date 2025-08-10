<?php

namespace App\Factory;

use App\Entity\PostalAddress;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<PostalAddress>
 */
final class PostalAddressFactory extends ObjectFactory
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
        return PostalAddress::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        return [
            'country' => 'France',
            'locality' => self::faker()->city(),
            'postalCode' => self::faker()->postcode(),
            'address' => self::faker()->streetAddress(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(PostalAddress $PostalAddress): void {})
        ;
    }
}
