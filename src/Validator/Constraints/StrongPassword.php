<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class StrongPassword extends Constraint
{
    public string $tooWeakMessage = 'Le mot de passe est trop faible (score : {{ score }}/4).';

    public string $withWarningMessage = 'Le mot de passe est trop faible (score : {{ score }}/4). {{ warning }}';

    public string $withSuggestionsMessage = 'Le mot de passe est trop faible (score : {{ score }}/4). Suggestions : {{ suggestions }}';

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public int $minScore = 2,
        ?array $groups = null,
        mixed $payload = null,
        array $options = [],
    ) {
        parent::__construct($options, $groups, $payload);
    }
}
