<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType as LicenseTypeEnum;
use App\Entity\License;
use App\Helper\LicenseTypeAgeCategoryMapping;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseFormType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ageCategory', ChoiceType::class, [
                'label' => 'Catégorie d\'âge',
                'choices' => LicenseAgeCategoryType::getChoices(),
                'required' => true,
                'attr' => [
                    'data-license-field' => 'ageCategory',
                    'data-age-category-mapping' => json_encode(LicenseTypeAgeCategoryMapping::getAllAgeCategoryMappings()),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => LicenseCategoryType::getChoices(),
                'required' => true,
                'attr' => [
                    'data-license-field' => 'category',
                    'data-category-types-mapping' => json_encode([
                        LicenseCategoryType::POUSSINS => LicenseTypeAgeCategoryMapping::getLicenseTypesForCategory(LicenseCategoryType::POUSSINS),
                        LicenseCategoryType::JEUNES => LicenseTypeAgeCategoryMapping::getLicenseTypesForCategory(LicenseCategoryType::JEUNES),
                        LicenseCategoryType::ADULTES => LicenseTypeAgeCategoryMapping::getLicenseTypesForCategory(LicenseCategoryType::ADULTES),
                    ]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de licence',
                'choices' => LicenseTypeEnum::getChoices(),
                'required' => true,
                'attr' => [
                    'data-license-field' => 'type',
                ],
            ])
            ->add('activities', ChoiceType::class, [
                'label' => 'Activités',
                'choices' => LicenseActivityType::getChoices(),
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => License::class,
        ]);
    }
}
