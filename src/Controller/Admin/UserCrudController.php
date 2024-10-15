<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DBAL\Types\GenderType;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Uid\Uuid;

class UserCrudController extends AbstractCrudController
{
    #[\Override]
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            ChoiceField::new('gender')
                ->setChoices(GenderType::getChoices())
                ->renderExpanded()
                ->hideOnIndex(),
            TextField::new('firstname'),
            TextField::new('lastname'),
            EmailField::new('email'),
            TelephoneField::new('phoneNumber'),
            AssociationField::new('licensees'),
        ];
    }

    #[\Override]
    public function createEntity(string $entityFqcn)
    {
        /** @var User $user */
        $user = parent::createEntity($entityFqcn);
        $user->setPassword(Uuid::v4());
        $user->setRoles(['ROLE_USER']);

        return $user;
    }

    #[\Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('lastname')
            ->add('firstname');
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort([
            'lastname' => 'ASC',
            'firstname' => 'ASC',
        ]);
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
