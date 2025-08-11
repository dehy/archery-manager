<?php

namespace App\Tests\Unit\Helper;

use App\Helper\SyncReturnValues;
use PHPUnit\Framework\TestCase;

class SyncReturnValuesTest extends TestCase
{
    public function testConstants(): void
    {
        $this->assertEquals('untouched', SyncReturnValues::UNTOUCHED);
        $this->assertEquals('updated', SyncReturnValues::UPDATED);
        $this->assertEquals('created', SyncReturnValues::CREATED);
    }
}
