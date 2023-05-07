<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\ClubFilter;
use App\Controller\Admin\Filter\LicenseSeasonFilter;
use App\DBAL\Types\GenderType;
use App\Entity\Licensee;
use App\Helper\EmailHelper;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class LicenseeCrudController extends AbstractCrudController
{
    public function __construct(
        protected AdminUrlGenerator $urlGenerator,
        protected readonly EmailHelper $emailHelper
    ) {
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

        $impersonateAction = Action::new(
            'impersonate',
            'Usurper l\'identité',
            'fa-solid fa-user-secret'
        )->linkToUrl(fn (Licensee $licensee) => sprintf(
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
        return $filters
            ->add(LicenseSeasonFilter::new())
            ->add(ClubFilter::new())
            ->add('groups');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityManager->persist($entityInstance);
        $entityManager->beginTransaction();
        try {
            $this->emailHelper->sendWelcomeEmail($entityInstance);

            $entityManager->flush();
            $entityManager->commit();
        } catch (TransportExceptionInterface $exception) {
            $entityManager->rollback();
            throw $exception;
        }
    }

    public function resendWelcomeEmail(AdminContext $context): RedirectResponse
    {
        /** @var Licensee $licensee */
        $licensee = $context->getEntity()->getInstance();
        $this->emailHelper->sendWelcomeEmail($licensee);

        $detailUrl = $this->urlGenerator->setAction('detail')->generateUrl();

        return $this->redirect($detailUrl);
    }
}
