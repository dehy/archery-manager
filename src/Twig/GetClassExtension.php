<?php

declare(strict_types=1);

namespace App\Twig;

class GetClassExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'get_class')]
    public function getClass(object $object): string
    {
        return $object::class;
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'get_short_class')]
    public function getShortClass(object $object): string
    {
        $reflect = new \ReflectionClass($object);

        return $reflect->getShortName();
    }
}
