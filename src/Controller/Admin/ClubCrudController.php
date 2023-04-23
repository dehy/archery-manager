<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichImageField;
use App\Entity\Club;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ClubCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Club::class;
    }
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            VichImageField::new('logo'),
            ColorField::new('primaryColor'),
            EmailField::new('contactEmail'),
            TextField::new('fftaCode'),
        ];
    }
}
