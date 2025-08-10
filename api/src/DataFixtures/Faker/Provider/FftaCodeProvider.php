<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('foundry.faker_provider')]
final class FftaCodeProvider
{
    public static function fftaId(): int
    {
        return Base::randomNumber(6, true);
    }

    public static function fftaCode(?int $id): string
    {
        $id ??= self::fftaId();

        return \sprintf('%s%s', $id, strtoupper(Base::randomLetter()));
    }
}
