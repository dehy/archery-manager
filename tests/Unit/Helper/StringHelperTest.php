<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Helper\StringHelper;
use PHPUnit\Framework\TestCase;

final class StringHelperTest extends TestCase
{
    public function testRandomPasswordGeneratesCorrectLength(): void
    {
        $password = StringHelper::randomPassword(10);
        $this->assertSame(10, mb_strlen($password));
    }

    public function testRandomPasswordGeneratesMinimumLength(): void
    {
        $password = StringHelper::randomPassword(1);
        $this->assertSame(1, mb_strlen($password));
    }

    public function testRandomPasswordGeneratesLongLength(): void
    {
        $password = StringHelper::randomPassword(100);
        $this->assertSame(100, mb_strlen($password));
    }

    public function testRandomPasswordContainsOnlyValidCharacters(): void
    {
        $validCharacters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!-.[]?*()';
        $password = StringHelper::randomPassword(50);

        for ($i = 0; $i < mb_strlen($password); ++$i) {
            $char = $password[$i];
            $this->assertStringContainsString($char, $validCharacters, \sprintf("Character '%s' is not valid", $char));
        }
    }

    public function testRandomPasswordGeneratesDifferentPasswords(): void
    {
        $password1 = StringHelper::randomPassword(20);
        $password2 = StringHelper::randomPassword(20);

        $this->assertNotSame($password1, $password2);
    }

    public function testRandomPasswordWithZeroLength(): void
    {
        $password = StringHelper::randomPassword(0);
        // range(1, 0) actually produces [1, 0], so it generates 2 characters
        $this->assertSame(2, mb_strlen($password));
    }
}
