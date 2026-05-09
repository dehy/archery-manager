<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidMoveUserDestinationValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidMoveUserDestination) {
            throw new UnexpectedTypeException($constraint, ValidMoveUserDestination::class);
        }

        if (!\is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $choice = $value['user_choice'] ?? null;

        if ('new' === $choice) {
            $email = (string) ($value['email'] ?? '');

            if ('' === $email) {
                $this->context->buildViolation($constraint->emptyEmailMessage)
                    ->atPath('[email]')
                    ->addViolation();
            }
        } elseif (null === ($value['existing_user'] ?? null)) {
            $this->context->buildViolation($constraint->noUserSelectedMessage)
                ->atPath('[existing_user]')
                ->addViolation();
        }
    }
}
