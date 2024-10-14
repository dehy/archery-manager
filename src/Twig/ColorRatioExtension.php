<?php

declare(strict_types=1);

namespace App\Twig;

use App\Helper\ResultHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ColorRatioExtension extends AbstractExtension
{
    final public const string HEX_FORMAT = '#%02x%02x%02x';

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('color_ratio', ResultHelper::colorRatio(...)),
        ];
    }
}
