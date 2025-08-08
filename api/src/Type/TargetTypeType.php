<?php

declare(strict_types=1);

namespace App\Type;

enum TargetTypeType: string
{
    case Monospot = 'monospot';
    case Trispot = 'trispot';
    case Field = 'field';
    case Animal = 'animal';
    case Beursault = 'beursault';
}
