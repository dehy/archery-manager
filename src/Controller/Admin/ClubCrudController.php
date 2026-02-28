<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Field\VichImageField;
use App\Entity\Club;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
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
            TextField::new('departmentCode')->setHelp('Code département (ex: 33 pour Gironde)')->onlyOnForms(),
            TextField::new('regionCode')->setHelp('Code région (ex: NAQ pour Nouvelle-Aquitaine)')->onlyOnForms(),
            ArrayField::new('watchedDepartmentCodes')->setHelp('Codes départements à synchroniser depuis la FFTA')->onlyOnForms(),
            ArrayField::new('watchedRegionCodes')->setHelp('Codes régions à synchroniser depuis la FFTA')->onlyOnForms(),
        ];
    }
}
