<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SecurityLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class SecurityLogCrudController extends AbstractCrudController
{
    private const string ENTITY_LABEL = 'Journal de sécurité';

    #[\Override]
    public static function getEntityFqcn(): string
    {
        return SecurityLog::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular(self::ENTITY_LABEL)
            ->setEntityLabelInPlural(self::ENTITY_LABEL)
            ->setPageTitle(Crud::PAGE_INDEX, self::ENTITY_LABEL)
            ->setPageTitle(Crud::PAGE_DETAIL, static fn (SecurityLog $log): string => \sprintf('#%d - %s', $log->getId(), $log->getEventType()))
            ->setDefaultSort(['occurredAt' => 'DESC'])
            ->setPaginatorPageSize(50);
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        // Security logs are read-only - no create, edit, or delete
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE);
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            DateTimeField::new('occurredAt', 'Date/Heure')
                ->setFormat('dd/MM/yyyy HH:mm:ss')
                ->setTimezone('Europe/Paris'),
            ChoiceField::new('eventType', 'Type')
                ->setChoices([
                    'Échec connexion' => SecurityLog::EVENT_FAILED_LOGIN,
                    'Connexion réussie' => SecurityLog::EVENT_SUCCESS_LOGIN,
                    'Compte verrouillé' => SecurityLog::EVENT_ACCOUNT_LOCKED,
                    'Compte déverrouillé' => SecurityLog::EVENT_ACCOUNT_UNLOCKED,
                    'Inscription réussie' => SecurityLog::EVENT_SUCCESS_REGISTRATION,
                    'Réinit. mot de passe demandée' => SecurityLog::EVENT_PASSWORD_RESET_REQUESTED,
                    'Réinit. mot de passe réussie' => SecurityLog::EVENT_SUCCESS_PASSWORD_RESET,
                    'CAPTCHA échoué' => SecurityLog::EVENT_CAPTCHA_FAILED,
                    'Limite de taux' => SecurityLog::EVENT_RATE_LIMITED,
                    'Activité suspecte' => SecurityLog::EVENT_SUSPICIOUS_ACTIVITY,
                ])
                ->renderAsBadges([
                    SecurityLog::EVENT_FAILED_LOGIN => 'danger',
                    SecurityLog::EVENT_SUCCESS_LOGIN => 'success',
                    SecurityLog::EVENT_ACCOUNT_LOCKED => 'danger',
                    SecurityLog::EVENT_ACCOUNT_UNLOCKED => 'success',
                    SecurityLog::EVENT_SUCCESS_REGISTRATION => 'success',
                    SecurityLog::EVENT_PASSWORD_RESET_REQUESTED => 'info',
                    SecurityLog::EVENT_SUCCESS_PASSWORD_RESET => 'success',
                    SecurityLog::EVENT_CAPTCHA_FAILED => 'warning',
                    SecurityLog::EVENT_RATE_LIMITED => 'warning',
                    SecurityLog::EVENT_SUSPICIOUS_ACTIVITY => 'danger',
                ]),
            AssociationField::new('user', 'Utilisateur')
                ->setRequired(false)
                ->hideOnIndex(),
            EmailField::new('email', 'Email'),
            TextField::new('ipAddress', 'Adresse IP'),
            TextareaField::new('userAgent', 'User Agent')
                ->hideOnIndex(),
            TextareaField::new('details', 'Détails')
                ->hideOnIndex(),
        ];
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('occurredAt', 'Date'))
            ->add(TextFilter::new('eventType', 'Type d\'événement'))
            ->add(TextFilter::new('ipAddress', 'Adresse IP'))
            ->add(TextFilter::new('email', 'Email'))
            ->add(EntityFilter::new('user', 'Utilisateur'));
    }
}
