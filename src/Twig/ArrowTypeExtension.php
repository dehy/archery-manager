<?php

declare(strict_types=1);

namespace App\Twig;

use App\DBAL\Types\ArrowType;

class ArrowTypeExtension
{
    #[\Twig\Attribute\AsTwigFilter(name: 'arrow_type_readable')]
    public function readable(string $arrowType): string
    {
        return ArrowType::getReadableValue($arrowType);
    }
}
