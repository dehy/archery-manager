<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GetClassExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('get_class', $this->getClass(...)),
        ];
    }

    public function getClass(object $object): string
    {
        return $object::class;
    }
}