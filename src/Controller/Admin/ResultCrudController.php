<?php

namespace App\Controller\Admin;

use App\DBAL\Types\DisciplineType;
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
            IdField::new("id")->onlyOnIndex(),
            AssociationField::new("licensee"),
            AssociationField::new("event"),
            ChoiceField::new("discipline")->setChoices(
                DisciplineType::getChoices()
            ),
            IntegerField::new("distance"),
            IntegerField::new("score"),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add("licensee")
            ->add("event")
            ->add(
                ChoiceFilter::new("discipline")->setChoices(
                    DisciplineType::getChoices()
                )
            )
            ->add("distance");
    }
}
