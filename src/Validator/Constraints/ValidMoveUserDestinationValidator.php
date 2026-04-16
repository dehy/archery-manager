<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidMoveUserDestinationValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

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
            } elseif (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
                $this->context->buildViolation($constraint->invalidEmailFormatMessage)
                    ->setParameter('{{ email }}', $email)
                    ->atPath('[email]')
                    ->addViolation();
            } elseif ($this->userRepository->findOneByEmail($email) instanceof User) {
                $this->context->buildViolation($constraint->duplicateEmailMessage)
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
