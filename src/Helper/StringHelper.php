<?php

declare(strict_types=1);

namespace App\Helper;

class StringHelper
{
    /**
     * A PHP function that will generate a secure random password.
     *
     * @throws \Exception
     */
    public static function randomPassword(int $length): string
    {
        $characters =
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!-.[]?*()';
        $characterListLength = mb_strlen($characters, '8bit') - 1;

        return implode('', array_map(
            static fn (): string => $characters[random_int(0, $characterListLength)],
            range(1, $length),
        ));
    }
}
