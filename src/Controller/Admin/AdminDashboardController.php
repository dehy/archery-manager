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
        protected readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route("/admin", name: "admin")]
    public function index(): Response
    {
        //return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        //$adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        //return $this->redirect(
        //    $adminUrlGenerator
        //        ->setController(UserCrudController::class)
        //        ->generateUrl()
        //);

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class
        );
        /** @var ArrayCollection<Licensee> $licensees */
        $licensees = new ArrayCollection($licenseeRepository->findAll());

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
            if ($license = $licensee->getLicenseForSeason(intval(date("Y")))) {
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
        );

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
