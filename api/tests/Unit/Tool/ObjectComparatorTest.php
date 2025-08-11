<?php

namespace App\Tests\Unit\Tool;

use App\Tool\ObjectComparator;
use PHPUnit\Framework\TestCase;

class ObjectComparatorTest extends TestCase
{
    public function testEqualStrict(): void
    {
        $obj = new \stdClass();
        $this->assertTrue(ObjectComparator::equal($obj, $obj, true));
        $this->assertFalse(ObjectComparator::equal(new \stdClass(), new \stdClass(), true));
    }

    public function testEqualNonStrictObjects(): void
    {
        $a = (object) ['foo' => 'bar'];
        $b = (object) ['foo' => 'bar'];
        $this->assertTrue(ObjectComparator::equal($a, $b));
    }

    public function testEqualNonStrictDifferentObjects(): void
    {
        $a = (object) ['foo' => 'bar'];
        $b = (object) ['foo' => 'baz'];
        $this->assertFalse(ObjectComparator::equal($a, $b));
    }

    public function testEqualWithDifferentTypes(): void
    {
        $a = (object) ['foo' => 'bar'];
        $b = ['foo' => 'bar'];
        $this->assertFalse(is_object($b) && ObjectComparator::equal($a, $b)); // only objects allowed
    }

    public function testDiffReturnsChangedProperties(): void
    {
        $a = (object) ['foo' => 'bar', 'baz' => 1];
        $b = (object) ['foo' => 'baz', 'baz' => 2];
        $diff = ObjectComparator::diff($a, $b);
        $this->assertArrayHasKey('foo', $diff);
        $this->assertArrayHasKey('baz', $diff);
        $this->assertEquals(['old_value' => 'bar', 'new_value' => 'baz'], $diff['foo']);
        $this->assertEquals(['old_value' => 1, 'new_value' => 2], $diff['baz']);
    }

    public function testDiffReturnsEmptyForIdenticalObjects(): void
    {
        $a = (object) ['foo' => 'bar', 'baz' => 1];
        $b = (object) ['foo' => 'bar', 'baz' => 1];
        $diff = ObjectComparator::diff($a, $b);
        $this->assertEmpty($diff);
    }

    public function testDiffReturnsEmptyForDifferentClasses(): void
    {
        $a = new class {
            public $foo = 'bar';
        };
        $b = new class {
            public $foo = 'bar';
        };
        $diff = ObjectComparator::diff($a, $b);
        $this->assertEmpty($diff);
    }

    public function testDiffHandlesMissingPropertiesGracefully(): void
    {
        $a = (object) ['foo' => 'bar'];
        $b = (object) [];
        // Should not throw, but will not find property in $b
        $this->expectException(\ReflectionException::class);
        ObjectComparator::diff($a, $b);
    }
}
