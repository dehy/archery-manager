<?php

namespace App\Controller\Admin;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseType;
use App\Entity\Applicant;
use App\Entity\Event;
use App\Entity\Group;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\PracticeAdvice;
use App\Entity\Result;
use App\Entity\User;
use Dmishh\SettingsBundle\Entity\Setting;
use Dmishh\SettingsBundle\Manager\SettingsManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly SettingsManagerInterface $settingsManager
    ) {
    }

    #[Route("/admin", name: "admin")]
    public function index(): Response
    {
        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class
        );
        $licensees = new ArrayCollection(
            $licenseeRepository->findByLicenseYear(2023)
        );

        $usersCount = $licensees->count();
        $womenCount = 0;
        $menCount = 0;

        $licenseTypesCount = array_fill_keys(LicenseType::getValues(), 0);
        $licenseAgeCategoryCount = array_fill_keys(
            LicenseAgeCategoryType::getValues(),
            0
        );

        foreach ($licensees as $licensee) {
            if ($licensee->getGender() === GenderType::FEMALE) {
                $womenCount += 1;
            } else {
                $menCount += 1;
            }
            if ($license = $licensee->getLicenseForSeason(2023)) {
                $licenseTypesCount[$license->getType()] += 1;
                $licenseAgeCategoryCount[$license->getAgeCategory()] += 1;
            }
        }
        $licenseTypesCount = array_filter($licenseTypesCount, fn($v) => $v > 0);
        $licenseAgeCategoryCount = array_filter(
            $licenseAgeCategoryCount,
            fn($v) => $v > 0
        );

        return $this->render("admin/dashboard.html.twig", [
            "licensees" => [
                "women" => $womenCount,
                "men" => $menCount,
                "total" => $usersCount,
            ],
            "licensesTypes" => $licenseTypesCount,
            "licenseAgeCategories" => $licenseAgeCategoryCount,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle("Les Archers de Bordeaux Guyenne");
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard(
            "Tableau de bord",
            "fa-solid fa-gauge-high"
        );

        yield MenuItem::section();

        yield MenuItem::linkToCrud("Comptes", "fa-regular fa-at", User::class);
        yield MenuItem::linkToCrud(
            "Licenciés",
            "fa-solid fa-user",
            Licensee::class
        );
        yield MenuItem::linkToCrud(
            "Licences",
            "fa-solid fa-id-card",
            License::class
        );
        yield MenuItem::linkToCrud(
            "Groupes",
            "fa-solid fa-users",
            Group::class
        );
        yield MenuItem::linkToCrud(
            "Conseils",
            "fa-regular fa-comment",
            PracticeAdvice::class
        );

        yield MenuItem::section();

        yield MenuItem::linkToCrud(
            "Évènements",
            "fa-regular fa-calendar",
            Event::class
        );
        yield MenuItem::linkToCrud(
            "Résultats",
            "fa-solid fa-square-poll-vertical",
            Result::class
        );

        yield MenuItem::section();

        yield MenuItem::linkToRoute(
            "Stats Pré-inscriptions",
            "fa-solid fa-chart-simple",
            "app_admin_applicants_stats"
        );

        yield MenuItem::linkToCrud(
            "Pré-inscriptions",
            "fa-solid fa-user-plus",
            Applicant::class
        )->setController(ApplicantCrudController::class);
        yield MenuItem::linkToCrud(
            "Création des licenses",
            "fa-solid fa-id-badge",
            Applicant::class
        )->setController(RegistrationCrudController::class);

        yield MenuItem::section();

        yield MenuItem::linkToCrud(
            "Paramètres",
            "fa-solid fa-wrench",
            Setting::class
        );
        yield MenuItem::linkToUrl(
            "Audit",
            "fa-solid fa-user-secret",
            "/audit"
        )->setLinkTarget("_blank");

        yield MenuItem::section();

        yield MenuItem::linkToRoute(
            "Retour au site",
            "fa-solid fa-arrow-left",
            "app_homepage"
        );

        yield MenuItem::section();

        yield MenuItem::linkToLogout(
            "Déconnexion",
            "fa-solid fa-arrow-right-from-bracket"
        );
    }
}
