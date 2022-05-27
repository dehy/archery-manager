<?php

namespace App\Controller\Admin;

use App\DBAL\Types\PracticeLevelType;
use App\Entity\Applicant;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ApplicantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Applicant::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Postulant")
            ->setEntityLabelInPlural("Postulants");
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new("id")->hideOnForm(),
            DateTimeField::new("registeredAt", "Soumis le"),
            TextField::new("lastname", "Nom"),
            TextField::new("firstname", "Prénom"),
            DateField::new("birthdate", "Date de naissance"),
            ChoiceField::new("practiceLevel", "Niveau de pratique")->setChoices(
                PracticeLevelType::getChoices()
            ),
            TextField::new("licenseNumber", "License FFTA"),
            TelephoneField::new("phoneNumber", "Téléphone"),
            TextField::new("comment", "Commentaire"),
        ];
    }
}
