<?php

namespace App\Tests;

trait MakePropertyAccessibleTrait
{
    /**
     * @throws \ReflectionException
     */
    public function set($entity, $value, $propertyName = 'id'): void
    {
        $class = new \ReflectionClass($entity);
        $property = $class->getProperty($propertyName);

        $property->setValue($entity, $value);
    }
}
