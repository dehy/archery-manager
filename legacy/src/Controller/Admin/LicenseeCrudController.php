<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\ClubFilter;
use App\Controller\Admin\Filter\LicenseSeasonFilter;
use App\DBAL\Types\GenderType;
use App\Entity\Licensee;
use App\Helper\ClubHelper;
use App\Helper\EmailHelper;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LicenseeCrudController extends AbstractCrudController
{
    public function __construct(
        protected AdminUrlGenerator $urlGenerator,
        protected readonly EmailHelper $emailHelper,
        protected readonly ClubHelper $clubHelper,
    ) {
    }

    #[\Override]
    public static function getEntityFqcn(): string
    {
        return Licensee::class;
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        $attachmentsAction = Action::new(
            'licenseeAttachments',
            'Pièces jointes',
            'fa-solid fa-paperclip'
        )->linkToUrl(fn (Licensee $licensee): \EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface => $this->urlGenerator
            ->unsetAll()
            ->setController(LicenseeAttachmentCrudController::class)
            ->set('filters[event][comparison]', '=')
            ->set('filters[event][value]', $licensee->getId()));

        $impersonateAction = Action::new(
            'impersonate',
            'Usurper l\'identité',
            'fa-solid fa-user-secret'
        )->linkToUrl(fn (Licensee $licensee): string => \sprintf(
            '/?_switch_user=%s&_switch_licensee=%s',
            $licensee->getUser()->getEmail(),
            $licensee->getFftaMemberCode()
        ));

        $resendWelcomeEmail = Action::new(
            'resendWelcomeEmail',
            'Renvoyer le mail de bienvenue',
            'fa-solid fa-envelope'
        )->linkToCrudAction('resendWelcomeEmail');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $attachmentsAction)
            ->add(Crud::PAGE_INDEX, $impersonateAction)
            ->add(Crud::PAGE_DETAIL, $impersonateAction)
            ->add(Crud::PAGE_DETAIL, $resendWelcomeEmail);
    }

    #[\Override]
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

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(LicenseSeasonFilter::new())
            ->add(ClubFilter::new())
            ->add('groups');
    }

    public function resendWelcomeEmail(AdminContext $context): RedirectResponse
    {
        /** @var Licensee $licensee */
        $licensee = $context->getEntity()->getInstance();
        $club = $this->clubHelper->activeClubFor($licensee);
        $this->emailHelper->sendWelcomeEmail($licensee, $club);

        $detailUrl = $this->urlGenerator->setAction('detail')->generateUrl();

        return $this->redirect($detailUrl);
    }
}
