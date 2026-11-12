<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
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

class ContestEventCrudController extends AbstractCrudController
{
    public function __construct(protected AdminUrlGenerator $urlGenerator)
    {
    }

    #[\Override]
    public static function getEntityFqcn(): string
    {
        return ContestEvent::class;
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
            ->add(ChoiceFilter::new('type')->setChoices(EventType::getChoices()))
            ->add(ChoiceFilter::new('discipline')->setChoices(DisciplineType::getChoices()));
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        $attachmentsAction = Action::new(
            'eventAttachments',
            'Pièces jointes',
            'fa-solid fa-paperclip'
        )->linkToUrl(fn (Event $event): \EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface => $this->urlGenerator
            ->unsetAll()
            ->setController(EventAttachmentCrudController::class)
            ->set('filters[event][comparison]', '=')
            ->set('filters[event][value]', $event->getId()));

        $seeResultsAction = Action::new(
            'showEventResults',
            'Results',
        )->linkToUrl(fn (Event $event): \EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface => $this->urlGenerator
            ->unsetAll()
            ->setController(ResultCrudController::class)
            ->set('filters[event][comparison]', '=')
            ->set('filters[event][value]', $event->getId()));

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $attachmentsAction)
            ->add(Crud::PAGE_INDEX, $seeResultsAction);
    }
}
