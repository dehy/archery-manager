<?php

namespace App\Controller\Admin;

use App\DBAL\Types\LicenseType;
use App\DBAL\Types\PracticeLevelType;
use App\Entity\Applicant;
use App\Repository\ApplicantRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApplicantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Applicant::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Pré-inscrit")
            ->setEntityLabelInPlural("Pré-inscrits");
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new("id")->hideOnForm(),
            BooleanField::new("renewal", "Renouvellement")->renderAsSwitch(
                false
            ),
            DateTimeField::new("registeredAt", "Soumis le"),
            TextField::new("lastname", "Nom"),
            TextField::new("firstname", "Prénom"),
            DateField::new("birthdate", "Date de naissance"),
            IntegerField::new("age", "Âge")->hideOnForm(),
            ChoiceField::new("practiceLevel", "Niveau de pratique")->setChoices(
                PracticeLevelType::getChoices()
            ),
            TextField::new("licenseNumber", "N° Licence"),
            ChoiceField::new("licenseType", "Type de licence")->setChoices([
                "LOISIR" => "LOISIR",
                "COMPÉTITION" => "COMPÉTITION",
            ]),
            EmailField::new("email"),
            TelephoneField::new("phoneNumber", "Téléphone"),
            TextField::new("comment", "Commentaire"),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add("renewal")->add("season");
    }

    /**
     * @Route("/admin/applicants/stats", name="app_admin_applicants_stats")
     */
    public function applicantStatistics(
        ApplicantRepository $applicantRepository
    ): Response {
        /** @var Applicant[] $applicants */
        $applicants = $applicantRepository->findBy(["season" => 2023]);
        $new = $renewal = 0;
        $newAges = [
            "10 ans et moins" => 0,
            "Entre 11 et 12 ans" => 0,
            "Entre 13 et 14 ans" => 0,
            "Entre 15 et 17 ans" => 0,
            "Entre 18 et 20 ans" => 0,
            "Entre 21 et 39 ans" => 0,
            "Entre 40 et 59 ans" => 0,
            "60 ans et plus" => 0,
        ];
        foreach ($applicants as $applicant) {
            $age = $applicant->getAge();

            $new += $applicant->isRenewal() ? 0 : 1;
            $renewal += $applicant->isRenewal() ? 1 : 0;

            if (!$applicant->isRenewal()) {
                if ($age <= 10) {
                    $newAges["10 ans et moins"] += 1;
                } elseif ($age >= 11 && $age <= 12) {
                    $newAges["Entre 11 et 12 ans"] += 1;
                } elseif ($age >= 13 && $age <= 14) {
                    $newAges["Entre 13 et 14 ans"] += 1;
                } elseif ($age >= 15 && $age <= 17) {
                    $newAges["Entre 15 et 17 ans"] += 1;
                } elseif ($age >= 18 && $age <= 20) {
                    $newAges["Entre 18 et 20 ans"] += 1;
                } elseif ($age >= 21 && $age <= 39) {
                    $newAges["Entre 21 et 39 ans"] += 1;
                } elseif ($age >= 40 && $age <= 59) {
                    $newAges["Entre 40 et 59 ans"] += 1;
                } elseif ($age >= 60) {
                    $newAges["60 ans et plus"] += 1;
                }
            }
        }

        $stats = [
            "new" => $new,
            "renewal" => $renewal,
            "total" => count($applicants),
            "newAges" => $newAges,
        ];

        return $this->render("admin/pre_registration/stats.html.twig", [
            "stats" => $stats,
        ]);
    }
}
