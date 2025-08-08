<?php

declare(strict_types=1);

namespace App\Type;

enum PracticeLevelType: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
}
