<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class StrongPassword extends Constraint
{
    public int $minScore = 2;

    public string $tooWeakMessage = 'Le mot de passe est trop faible (score : {{ score }}/4).';

    public string $withWarningMessage = 'Le mot de passe est trop faible (score : {{ score }}/4). {{ warning }}';

    public string $withSuggestionsMessage = 'Le mot de passe est trop faible (score : {{ score }}/4). Suggestions : {{ suggestions }}';
}
