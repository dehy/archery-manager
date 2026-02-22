<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Entity\License;
use App\Helper\LicenseTypeAgeCategoryMapping;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidLicenseCombinationValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidLicenseCombination) {
            throw new UnexpectedTypeException($constraint, ValidLicenseCombination::class);
        }

        if (!$value instanceof License) {
            throw new UnexpectedValueException($value, License::class);
        }

        $license = $value;
        $ageCategory = $license->getAgeCategory();
        $category = $license->getCategory();
        $type = $license->getType();

        // Skip validation if any required field is missing
        if (!$ageCategory || !$category || !$type) {
            return;
        }

        // Validate age category matches category
        if (!LicenseTypeAgeCategoryMapping::isValidAgeCategoryForCategory($ageCategory, $category)) {
            $this->context->buildViolation($constraint->invalidAgeCategoryMessage)
                ->setParameter('{{ ageCategory }}', $ageCategory)
                ->setParameter('{{ category }}', $category)
                ->atPath('ageCategory')
                ->addViolation();
        }

        // Validate license type matches category
        if (!LicenseTypeAgeCategoryMapping::isValidLicenseTypeForCategory($type, $category)) {
            $this->context->buildViolation($constraint->invalidTypeMessage)
                ->setParameter('{{ type }}', $type)
                ->setParameter('{{ category }}', $category)
                ->atPath('type')
                ->addViolation();
        }
    }
}
