<?php

declare(strict_types=1);

namespace App\Twig;

use App\Helper\MapHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MapExtension extends AbstractExtension
{
    public function __construct(private readonly MapHelper $mapHelper)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('address_map_url', $this->mapHelper->urlForAddress(...)),
            new TwigFunction('longlat_map_url', $this->mapHelper->urlForLongLat(...)),
        ];
    }
}
