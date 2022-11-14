<?php

namespace App\Controller\Admin;

use App\DBAL\Types\EventParticipationStateType;
use App\Entity\EventParticipation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class EventParticipationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EventParticipation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('event'),
            AssociationField::new('participant'),
            IntegerField::new('departure'),
            ChoiceField::new('participationState')->setChoices(EventParticipationStateType::getChoices()),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('event')->add('participant')->add('participationState');
    }
}
