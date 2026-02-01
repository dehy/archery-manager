<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidLicenseCombination extends Constraint
{
    public string $invalidAgeCategoryMessage = 'La catégorie d\'âge "{{ ageCategory }}" n\'est pas valide pour la catégorie "{{ category }}".';

    public string $invalidTypeMessage = 'Le type de licence "{{ type }}" n\'est pas valide pour la catégorie "{{ category }}".';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
