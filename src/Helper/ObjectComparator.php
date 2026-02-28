<?php

declare(strict_types=1);

namespace App\Helper;

class ObjectComparator
{
    /**
     * Compare 2 objects.
     *
     * @param $strict bool Compare in simple (==) or in strict way (===)
     *
     * @return bool true objects are equals, false objects are different
     */
    public static function equal(object $o1, object $o2, bool $strict = false): bool
    {
        return $strict ? $o1 === $o2 : $o1 == $o2;
    }

    /**
     * Find the differences between 2 objects using Reflection.
     *
     * @return array Properties that have changed
     *
     * @throws \InvalidArgumentException|\ReflectionException
     */
    public static function diff(object $o1, object $o2): array
    {
        $diff = [];
        if ($o1::class === $o2::class) {
            $o1Properties = new \ReflectionObject($o1)->getProperties();
            $o2Reflected = new \ReflectionObject($o2);

            foreach ($o1Properties as $o1Property) {
                $o2Property = $o2Reflected->getProperty($o1Property->getName());
                if (($oldValue = $o1Property->getValue($o1)) != ($newValue = $o2Property->getValue($o2))) { // NOSONAR
                    $diff[$o1Property->getName()] = [
                        'old_value' => $oldValue,
                        'new_value' => $newValue,
                    ];
                }
            }
        }

        return $diff;
    }
}
