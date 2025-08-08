<?php

namespace App\Factory;

use App\Entity\License;
use App\Type\GenderType;
use App\Type\LicenseActivityType;
use App\Type\LicenseAgeCategoryType;
use App\Type\LicenseCategoryType;
use App\Type\LicenseType;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<License>
 */
final class LicenseFactory extends PersistentProxyObjectFactory
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
        return License::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        return [
            'season' => 2025,
            'type' => LicenseType::ADULTES_CLUB,
            'category' => LicenseCategoryType::ADULTES,
            'ageCategory' => LicenseAgeCategoryType::SENIOR_1,
            'activities' => [LicenseActivityType::CL],
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
