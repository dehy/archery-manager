<?php

declare(strict_types=1);

namespace App\Type;

enum DisciplineType: string
{
    case Target = 'target';
    case Indoor = 'indoor';
    case Field = 'field';
    case Nature = 'nature';
    case ThreeD = '3d';
    case Para = 'para';
    case Run = 'run';
}
