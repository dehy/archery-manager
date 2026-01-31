<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\BowType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class BowTypeExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('bow_type_readable', $this->readable(...)),
        ];
    }

    public function readable(string $bowType): string
    {
        return BowType::getReadableValue($bowType);
    }
}
