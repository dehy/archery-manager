<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\LicenseSeasonFilter;
use App\DBAL\Types\GenderType;
use App\Entity\Licensee;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class LicenseeCrudController extends AbstractCrudController
{
    public function __construct(protected AdminUrlGenerator $urlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Licensee::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $attachmentsAction = Action::new(
            'licenseeAttachments',
            'Pièces jointes',
            'fa-solid fa-paperclip'
        )->linkToUrl(fn (Licensee $licensee) => $this->urlGenerator
            ->unsetAll()
            ->setController(LicenseeAttachmentCrudController::class)
            ->set('filters[event][comparison]', '=')
            ->set('filters[event][value]', $licensee->getId()));

        return $actions->add(Crud::PAGE_INDEX, $attachmentsAction);
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
            AssociationField::new('groups')->setFormTypeOption('by_reference', false)
                ->setTemplatePath('admin/crud/fields/group.html.twig'),
            DateField::new('birthdate'),
            TextField::new('fftaMemberCode'),
            IntegerField::new('fftaId')->hideOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(LicenseSeasonFilter::new())
            ->add('groups');
    }
}
