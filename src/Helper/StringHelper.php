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
        $password = '';

        foreach (range(1, $length) as $_) { // $_ is an intentionally unused loop variable (NOSONAR: php:S1481)
            $password .= $characters[random_int(0, $characterListLength)];
        }

        return $password;
    }
}
