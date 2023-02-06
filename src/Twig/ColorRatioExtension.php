<?php

namespace App\Twig;

use App\Helper\ResultHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ColorRatioExtension extends AbstractExtension
{
    final public const HEX_FORMAT = '#%02x%02x%02x';
    public function getFunctions(): array
    {
        return [
            new TwigFunction('color_ratio', ResultHelper::colorRatio(...)),
        ];
    }
}
