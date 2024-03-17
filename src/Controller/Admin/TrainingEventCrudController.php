<?php

namespace App\Controller\Admin;

use App\DBAL\Types\DisciplineType;
use App\Entity\TrainingEvent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class TrainingEventCrudController extends AbstractCrudController
{
    public function __construct(protected AdminUrlGenerator $urlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return TrainingEvent::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('club'),
            TextField::new('name'),
            DateField::new('startDate'),
            TimeField::new('startTime'),
            DateField::new('endDate'),
            TimeField::new('endTime'),
            BooleanField::new('fullDayEvent')->renderAsSwitch(false),
            ChoiceField::new('discipline')->setChoices(
                DisciplineType::getChoices(),
            ),
            TextField::new('address'),
            TextField::new('latitude'),
            TextField::new('longitude'),
            AssociationField::new('assignedGroups'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort([
            'startDate' => 'DESC',
            'startTime' => 'DESC',
            'endDate' => 'DESC',
            'endTime' => 'DESC'
        ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add(EntityFilter::new('club'))
            ->add(EntityFilter::new('assignedGroups'))
            ->add(ChoiceFilter::new('discipline')->setChoices(DisciplineType::getChoices()));
    }
}
