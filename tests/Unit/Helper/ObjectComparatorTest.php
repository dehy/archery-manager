<?php

namespace App\Tests\Unit\Helper;

use App\Helper\ObjectComparator;
use PHPUnit\Framework\TestCase;

class ObjectComparatorTest extends TestCase
{
    public function testEqual(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 12;
        $obj2 = new \stdClass();
        $obj2->id = 12;

        self::assertTrue(ObjectComparator::equal($obj1, $obj2));
        self::assertFalse(ObjectComparator::equal($obj1, $obj2, true));
    }

    public function testEqualStrict(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 12;
        $obj2 = $obj1;

        self::assertTrue(ObjectComparator::equal($obj1, $obj2));
        self::assertTrue(ObjectComparator::equal($obj1, $obj2, true));
    }

    public function testNotEqual(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 11;
        $obj2 = new \stdClass();
        $obj2->id = 12;

        self::assertFalse(ObjectComparator::equal($obj1, $obj2));
        self::assertFalse(ObjectComparator::equal($obj1, $obj2, true));
    }

    public function testDiff(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 11;
        $obj2 = new \stdClass();
        $obj2->id = 12;

        $diff = ObjectComparator::diff($obj1, $obj2);

        self::assertEquals([
            'id' => [
                'old_value' => 11,
                'new_value' => 12,
            ],
        ], $diff);
    }

    public function testNoDiff(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 12;
        $obj2 = new \stdClass();
        $obj2->id = 12;

        $diff = ObjectComparator::diff($obj1, $obj2);

        self::assertEquals([], $diff);
    }
}
