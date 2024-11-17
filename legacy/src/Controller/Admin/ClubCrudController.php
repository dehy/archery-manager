<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichImageField;
use App\Entity\Club;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class ClubCrudController extends AbstractCrudController
{
    #[\Override]
    public static function getEntityFqcn(): string
    {
        return Club::class;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextField::new('city'),
            VichImageField::new('logo'),
            ColorField::new('primaryColor'),
            EmailField::new('contactEmail'),
            TextField::new('fftaCode'),
            TextField::new('fftaUsername')->onlyOnForms(),
            TextField::new('fftaPassword')->onlyOnForms()->setFormType(PasswordType::class),
        ];
    }
}
