<?php

namespace App\Factory;

use App\Entity\Licensee;
use App\Type\GenderType;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Licensee>
 */
final class LicenseeFactory extends PersistentProxyObjectFactory
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
        return Licensee::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        $gender = self::faker()->randomElement(GenderType::cases());
        $fftaId = self::faker()->randomNumber(6, true);
        $fftaCode = sprintf('%06d%s', $fftaId, self::faker()->randomLetter());

        return [
            'birthDate' => self::faker()->dateTimeBetween('-65 years', '-12 years'),
            'fftaId' => $fftaId,
            'fftaMemberCode' => $fftaCode,
            'givenName' => self::faker()->firstName($gender),
            'gender' => $gender,
            'familyName' => self::faker()->lastName($gender),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Licensee $licensee): void {})
        ;
    }
}
