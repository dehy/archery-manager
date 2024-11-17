<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GetClassExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('get_class', $this->getClass(...)),
            new TwigFilter('get_short_class', $this->getShortClass(...)),
        ];
    }

    public function getClass(object $object): string
    {
        return $object::class;
    }

    public function getShortClass(object $object): string
    {
        $reflect = new \ReflectionClass($object);

        return $reflect->getShortName();
    }
}
