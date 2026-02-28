<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventScopeType;
use App\Entity\HobbyContestEvent;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class HobbyContestEventCrudController extends ContestEventCrudController
{
    #[\Override]
    public static function getEntityFqcn(): string
    {
        return HobbyContestEvent::class;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            DateTimeField::new('startsAt'),
            DateTimeField::new('endsAt'),
            BooleanField::new('allDay')->renderAsSwitch(false),
            ChoiceField::new('scope')->setChoices(EventScopeType::getChoices()),
            ChoiceField::new('contestType')->setChoices(
                ContestType::getChoices(),
            ),
            ChoiceField::new('discipline')->setChoices(
                DisciplineType::getChoices(),
            ),
            TextField::new('address'),
            TextField::new('latitude'),
            TextField::new('longitude'),
            AssociationField::new('assignedGroups'),
            BooleanField::new('hasMandate', 'Mandat')->renderAsSwitch(false)->hideOnForm(),
            BooleanField::new('hasResults', 'Résultats')->renderAsSwitch(false)->hideOnForm(),
        ];
    }
}
