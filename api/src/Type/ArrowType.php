<?php

declare(strict_types=1);

namespace App\Type;

enum ArrowType: string
{
    case Wood = 'wood';
    case Aluminum = 'aluminum';
    case Carbon = 'carbon';
    case AluminumCarbon = 'aluminum_carbon';
}
