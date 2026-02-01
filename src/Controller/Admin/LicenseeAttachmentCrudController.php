<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\LicenseeAttachment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Vich\UploaderBundle\Form\Type\VichImageType;

class LicenseeAttachmentCrudController extends AbstractCrudController
{
    #[\Override]
    public static function getEntityFqcn(): string
    {
        return LicenseeAttachment::class;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        $downloadAction = Action::new('downloadFile', 'Voir', 'fa-solid fa-eye')
            ->linkToRoute(
                'licensees_attachements_download',
                static fn (LicenseeAttachment $attachment): array => [
                    'attachment' => $attachment->getId(),
                ]
            );

        return $actions->add(Crud::PAGE_INDEX, $downloadAction);
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('licensee', 'Licencié'),
            IntegerField::new('season', 'Saison'),
            ChoiceField::new('type', 'Type')->setChoices(static fn (): array => LicenseeAttachmentType::getChoices()),
            DateField::new('documentDate', 'Date du document'),
            TextField::new('uploadedFile', 'Fichier')->setFormType(VichImageType::class),
        ];
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('licensee')->add('season');
    }
}
