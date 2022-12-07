<?php

namespace App\Controller\Admin;

use App\DBAL\Types\EventAttachmentType;
use App\Entity\EventAttachment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EventAttachmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EventAttachment::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $downloadAction = Action::new('downloadFile', 'Voir', 'fa-solid fa-eye')
            ->linkToRoute(
                'events_attachements_download',
                fn (EventAttachment $attachment) => [
                    'attachment' => $attachment->getId(),
                ]
            );

        return $actions->add(Crud::PAGE_INDEX, $downloadAction);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('event', 'Évènement'),
            ChoiceField::new('type', 'Type')->setChoices(fn () => EventAttachmentType::getChoices()),
            TextField::new('uploadedFile', 'Fichier')->setFormType(VichImageType::class),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('event'));
    }
}
