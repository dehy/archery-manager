<?php

namespace App\Controller\Admin;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
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
            IntegerField::new('distance'),
            IntegerField::new('targetSize'),
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
