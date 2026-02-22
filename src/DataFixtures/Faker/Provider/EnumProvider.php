<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;
use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class EnumProvider extends Base
{
    public static function enum(string $enumClass): int|string
    {
        /** @var class-string<AbstractEnumType> $class */
        $class = 'App\\DBAL\\Types\\'.$enumClass;
        $values = $class::getValues();
        $randomKey = self::numberBetween(0, \count($values) - 1);

        return $values[$randomKey];
    }
}
