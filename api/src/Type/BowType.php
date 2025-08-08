<?php

declare(strict_types=1);

namespace App\Type;

enum BowType: string
{
    case Recurve = 'recurve';
    case Compound = 'compound';
    case Traditional = 'traditional';
    case Barebow = 'barebow';
}
