<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\FletchingType;

class FletchingTypeExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'fletching_type_readable')]
    public function readable(string $fletchingType): string
    {
        return FletchingType::getReadableValue($fletchingType);
    }
}
