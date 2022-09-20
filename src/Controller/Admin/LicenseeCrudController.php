<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\LicenseSeasonFilter;
use App\DBAL\Types\GenderType;
use App\Entity\Licensee;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LicenseeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Licensee::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user'),
            ChoiceField::new('gender')
                ->setChoices(GenderType::getChoices())
                ->renderExpanded(),
            TextField::new('firstname'),
            TextField::new('lastname'),
            DateField::new('birthdate'),
            TextField::new('fftaMemberCode'),
            IntegerField::new('fftaId')->hideOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(LicenseSeasonFilter::new());
    }
}
