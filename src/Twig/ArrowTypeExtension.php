<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\ArrowType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ArrowTypeExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('arrow_type_readable', $this->readable(...)),
        ];
    }

    public function readable(string $arrowType): string
    {
        return ArrowType::getReadableValue($arrowType);
    }
}
