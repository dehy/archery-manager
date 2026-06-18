<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidMoveUserDestination extends Constraint
{
    public string $emptyEmailMessage = 'Veuillez saisir une adresse email.';

    public string $noUserSelectedMessage = 'Veuillez sélectionner un compte existant.';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
