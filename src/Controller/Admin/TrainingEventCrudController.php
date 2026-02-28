<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventScopeType;
use App\Entity\TrainingEvent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class TrainingEventCrudController extends AbstractCrudController
{
    public function __construct(protected AdminUrlGenerator $urlGenerator)
    {
    }

    #[\Override]
    public static function getEntityFqcn(): string
    {
        return TrainingEvent::class;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('club'),
            TextField::new('name'),
            DateTimeField::new('startsAt'),
            DateTimeField::new('endsAt'),
            BooleanField::new('allDay')->renderAsSwitch(false),
            ChoiceField::new('scope')->setChoices(EventScopeType::getChoices()),
            ChoiceField::new('discipline')->setChoices(
                DisciplineType::getChoices(),
            ),
            TextField::new('address'),
            TextField::new('latitude'),
            TextField::new('longitude'),
            AssociationField::new('assignedGroups'),
        ];
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['startsAt' => 'DESC', 'endsAt' => 'DESC']);
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add(EntityFilter::new('club'))
            ->add(EntityFilter::new('assignedGroups'))
            ->add(ChoiceFilter::new('discipline')->setChoices(DisciplineType::getChoices()));
    }
}
