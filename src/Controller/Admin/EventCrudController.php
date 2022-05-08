<?php

namespace App\Controller\Admin;

use App\Admin\Field\EnumTypeField;
use App\DBAL\Types\EventType;
use App\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new("name"),
            DateTimeField::new("startsAt"),
            DateTimeField::new("endsAt"),
            ChoiceField::new("type")
                ->setChoices(EventType::getChoices())
                ->renderExpanded(),
            TextField::new("address"),
        ];
    }
}
