<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator\Constraints;

use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\License;
use App\Validator\Constraints\ValidLicenseCombination;
use App\Validator\Constraints\ValidLicenseCombinationValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ValidLicenseCombinationValidator>
 */
final class ValidLicenseCombinationValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ValidLicenseCombinationValidator
    {
        return new ValidLicenseCombinationValidator();
    }

    public function testNullFieldsSkipValidation(): void
    {
        $license = new License();
        // No ageCategory, category, or type set

        $this->validator->validate($license, new ValidLicenseCombination());
        $this->assertNoViolation();
    }

    public function testValidAdultesCombination(): void
    {
        $license = new License();
        $license->setAgeCategory(LicenseAgeCategoryType::SENIOR_1);
        $license->setCategory(LicenseCategoryType::ADULTES);
        $license->setType(LicenseType::ADULTES_COMPETITION);

        $this->validator->validate($license, new ValidLicenseCombination());
        $this->assertNoViolation();
    }

    public function testValidJeunesCombination(): void
    {
        $license = new License();
        $license->setAgeCategory(LicenseAgeCategoryType::U18);
        $license->setCategory(LicenseCategoryType::JEUNES);
        $license->setType(LicenseType::JEUNES);

        $this->validator->validate($license, new ValidLicenseCombination());
        $this->assertNoViolation();
    }

    public function testValidPoussinsCombination(): void
    {
        $license = new License();
        $license->setAgeCategory(LicenseAgeCategoryType::U11);
        $license->setCategory(LicenseCategoryType::POUSSINS);
        $license->setType(LicenseType::POUSSINS);

        $this->validator->validate($license, new ValidLicenseCombination());
        $this->assertNoViolation();
    }

    public function testInvalidAgeCategoryForCategory(): void
    {
        $license = new License();
        $license->setAgeCategory(LicenseAgeCategoryType::SENIOR_1); // adult age
        $license->setCategory(LicenseCategoryType::JEUNES);          // youth category
        $license->setType(LicenseType::JEUNES);

        $this->validator->validate($license, new ValidLicenseCombination());

        $this->buildViolation(
            'La catégorie d\'âge "{{ ageCategory }}" n\'est pas valide pour la catégorie "{{ category }}".'
        )
            ->setParameter('{{ ageCategory }}', LicenseAgeCategoryType::SENIOR_1)
            ->setParameter('{{ category }}', LicenseCategoryType::JEUNES)
            ->atPath('property.path.ageCategory')
            ->assertRaised();
    }

    public function testInvalidLicenseTypeForCategory(): void
    {
        $license = new License();
        $license->setAgeCategory(LicenseAgeCategoryType::U18);
        $license->setCategory(LicenseCategoryType::JEUNES);
        $license->setType(LicenseType::ADULTES_COMPETITION); // adult type with youth category

        $this->validator->validate($license, new ValidLicenseCombination());

        $this->buildViolation(
            'Le type de licence "{{ type }}" n\'est pas valide pour la catégorie "{{ category }}".'
        )
            ->setParameter('{{ type }}', LicenseType::ADULTES_COMPETITION)
            ->setParameter('{{ category }}', LicenseCategoryType::JEUNES)
            ->atPath('property.path.type')
            ->assertRaised();
    }

    public function testBothAgeCategoryAndTypeInvalid(): void
    {
        $license = new License();
        $license->setAgeCategory(LicenseAgeCategoryType::SENIOR_1); // adult age
        $license->setCategory(LicenseCategoryType::JEUNES);          // youth category
        $license->setType(LicenseType::ADULTES_COMPETITION);        // adult type

        $this->validator->validate($license, new ValidLicenseCombination());

        // Should have two violations
        $violations = $this->context->getViolations();
        $this->assertCount(2, $violations);
    }

    public function testRejectsNonLicenseValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate('not-a-license', new ValidLicenseCombination());
    }

    public function testRejectsInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new License(), $this->createStub(\Symfony\Component\Validator\Constraint::class));
    }

    public function testPartialFieldsSkipValidation(): void
    {
        $license = new License();
        $license->setAgeCategory(LicenseAgeCategoryType::SENIOR_1);
        // category and type not set

        $this->validator->validate($license, new ValidLicenseCombination());
        $this->assertNoViolation();
    }

    public function testAllAdulteTypesAreValid(): void
    {
        foreach ([LicenseType::ADULTES_COMPETITION, LicenseType::ADULTES_CLUB, LicenseType::ADULTES_SANS_PRATIQUE] as $type) {
            $license = new License();
            $license->setAgeCategory(LicenseAgeCategoryType::SENIOR_2);
            $license->setCategory(LicenseCategoryType::ADULTES);
            $license->setType($type);

            // Create a fresh validator + context for each iteration
            $validator = new ValidLicenseCombinationValidator();
            $validator->initialize($this->createValidationContext());

            $validator->validate($license, new ValidLicenseCombination());
        }

        // If we get here without exception, all types are valid
        $this->assertTrue(true);
    }

    private function createValidationContext(): \Symfony\Component\Validator\Context\ExecutionContextInterface
    {
        $context = $this->createMock(\Symfony\Component\Validator\Context\ExecutionContextInterface::class);
        $context->method('buildViolation')->willThrowException(
            new \RuntimeException('Unexpected violation')
        );

        return $context;
    }
}
