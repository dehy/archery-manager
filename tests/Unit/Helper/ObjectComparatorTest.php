<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Helper\ObjectComparator;
use PHPUnit\Framework\TestCase;

final class ObjectComparatorTest extends TestCase
{
    public function testEqual(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 12;
        $obj2 = new \stdClass();
        $obj2->id = 12;

        $this->assertTrue(ObjectComparator::equal($obj1, $obj2));
        $this->assertFalse(ObjectComparator::equal($obj1, $obj2, true));
    }

    public function testEqualStrict(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 12;
        $obj2 = $obj1;

        $this->assertTrue(ObjectComparator::equal($obj1, $obj2));
        $this->assertTrue(ObjectComparator::equal($obj1, $obj2, true));
    }

    public function testNotEqual(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 11;
        $obj2 = new \stdClass();
        $obj2->id = 12;

        $this->assertFalse(ObjectComparator::equal($obj1, $obj2));
        $this->assertFalse(ObjectComparator::equal($obj1, $obj2, true));
    }

    public function testDiff(): void
    {
        $obj1 = new \stdClass();
        $obj1->id = 11;
        $obj2 = new \stdClass();
        $obj2->id = 12;

        $diff = ObjectComparator::diff($obj1, $obj2);

        $this->assertSame([
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

        $this->assertSame([], $diff);
    }
}
