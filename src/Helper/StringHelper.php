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
        $password = '';
        $characterListLength = mb_strlen($characters, '8bit') - 1;
        foreach (range(1, $length) as $index) {
            $password .= $characters[random_int(0, $characterListLength)];
        }

        return $password;
    }
}
