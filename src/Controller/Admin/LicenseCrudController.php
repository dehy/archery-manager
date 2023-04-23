<?php

namespace App\Controller\Admin;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\License;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class LicenseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return License::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('licensee'),
            IntegerField::new('season'),
            AssociationField::new('club'),
            ChoiceField::new('type')->setChoices(LicenseType::getChoices()),
            ChoiceField::new('category')->setChoices(
                LicenseCategoryType::getChoices(),
            ),
            ChoiceField::new('ageCategory')->setChoices(
                LicenseAgeCategoryType::getChoices(),
            ),
            ChoiceField::new('activities')
                ->setChoices(LicenseActivityType::getChoices())
                ->allowMultipleChoices(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('club'))
            ->add('licensee')
            ->add('season');
    }
}
