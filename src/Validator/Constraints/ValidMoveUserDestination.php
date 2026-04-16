<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidMoveUserDestination extends Constraint
{
    public string $emptyEmailMessage = 'Veuillez saisir une adresse email.';

    public string $invalidEmailFormatMessage = 'L\'adresse email "{{ email }}" n\'est pas valide.';

    public string $duplicateEmailMessage = 'Cette adresse email est déjà utilisée par un autre compte.';

    public string $noUserSelectedMessage = 'Veuillez sélectionner un compte existant.';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
