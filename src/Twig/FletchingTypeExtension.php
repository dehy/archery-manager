<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\FletchingType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FletchingTypeExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('fletching_type_readable', $this->readable(...)),
        ];
    }

    public function readable(string $fletchingType): string
    {
        return FletchingType::getReadableValue($fletchingType);
    }
}
