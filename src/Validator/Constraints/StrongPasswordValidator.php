<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use ZxcvbnPhp\Zxcvbn;

class StrongPasswordValidator extends ConstraintValidator
{
    private const string PLACEHOLDER_SCORE = '{{ score }}';

    private readonly Zxcvbn $zxcvbn;

    public function __construct()
    {
        $this->zxcvbn = new Zxcvbn();
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }

        // Allow null and empty strings (let NotBlank constraint handle that)
        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Analyze password strength
        $result = $this->zxcvbn->passwordStrength($value);
        $score = $result['score'];

        // Check if password meets minimum score requirement
        if ($score < $constraint->minScore) {
            $warning = $result['feedback']['warning'] ?? '';
            $suggestions = $result['feedback']['suggestions'] ?? [];

            // Build violation message based on available feedback
            if (!empty($warning) && !empty($suggestions)) {
                $this->context->buildViolation($constraint->withSuggestionsMessage)
                    ->setParameter(self::PLACEHOLDER_SCORE, (string) $score)
                    ->setParameter('{{ warning }}', $warning)
                    ->setParameter('{{ suggestions }}', implode(', ', $suggestions))
                    ->addViolation();
            } elseif (!empty($warning)) {
                $this->context->buildViolation($constraint->withWarningMessage)
                    ->setParameter(self::PLACEHOLDER_SCORE, (string) $score)
                    ->setParameter('{{ warning }}', $warning)
                    ->addViolation();
            } else {
                $this->context->buildViolation($constraint->tooWeakMessage)
                    ->setParameter(self::PLACEHOLDER_SCORE, (string) $score)
                    ->addViolation();
            }
        }
    }
}
