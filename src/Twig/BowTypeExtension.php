<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\BowType;

class BowTypeExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'bow_type_readable')]
    public function readable(string $bowType): string
    {
        return BowType::getReadableValue($bowType);
    }
}
