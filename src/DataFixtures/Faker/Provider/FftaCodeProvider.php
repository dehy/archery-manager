<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;

final class FftaCodeProvider extends Base
{
    public static function fftaId(): int
    {
        return Base::randomNumber(6, true);
    }

    public static function fftaCode(int $id): string
    {
        return \sprintf('%s%s', $id, strtoupper(Base::randomLetter()));
    }
}
