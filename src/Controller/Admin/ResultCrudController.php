<?php

namespace App\Controller\Admin;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\TargetTypeType;
use App\Entity\Result;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class ResultCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Result::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('licensee'),
            AssociationField::new('event'),
            ChoiceField::new('discipline')->setChoices(
                DisciplineType::getChoices(),
            ),
            ChoiceField::new('ageCategory')->setChoices(
                LicenseAgeCategoryType::getChoices(),
            ),
            ChoiceField::new('activity')->setChoices(
                LicenseActivityType::getChoices(),
            ),
            IntegerField::new('distance')->setLabel('Distance (en mètres)'),
            ChoiceField::new('targetType')->setChoices(
                TargetTypeType::getChoices(),
            ),
            IntegerField::new('targetSize')->setLabel('Taille (en centimètres)'),
            IntegerField::new('score1'),
            IntegerField::new('score2'),
            IntegerField::new('total'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('licensee')
            ->add('event')
            ->add(
                ChoiceFilter::new('discipline')->setChoices(
                    DisciplineType::getChoices(),
                ),
            )
            ->add('distance')
            ->add(ChoiceFilter::new('targetType')->setChoices(
                TargetTypeType::getChoices(),
            ))
            ->add('targetSize')
            ->add(
                ChoiceFilter::new('ageCategory')->setChoices(
                    LicenseAgeCategoryType::getChoices(),
                ),
            )
            ->add(
                ChoiceFilter::new('activity')->setChoices(
                    LicenseActivityType::getChoices(),
                ),
            )
        ;
    }
}
