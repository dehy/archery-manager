<?php

namespace App\Controller\Admin;

use App\Admin\Field\EnumTypeField;
use App\DBAL\Types\GenderType;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Uid\Uuid;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Archer")
            ->setEntityLabelInPlural("Archers");
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new("id")->hideOnForm(),
            ChoiceField::new("gender")
                ->setChoices(GenderType::getChoices())
                ->renderExpanded(),
            TextField::new("firstname"),
            TextField::new("lastname"),
            EmailField::new("email"),
            DateField::new("birthdate"),
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        /** @var User $user */
        $user = parent::createEntity($entityFqcn);
        $user->setPassword(Uuid::v4());
        $user->setRoles(["ROLE_USER"]);

        return $user;
    }
}
