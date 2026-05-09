<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class UniqueUserEmail extends Constraint
{
    public string $message = 'Cette adresse email est déjà utilisée par un autre compte.';
}
