<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\Result;
use App\Entity\User;
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
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route("/admin", name: "admin")]
    public function index(): Response
    {
        //return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect(
            $adminUrlGenerator
                ->setController(UserCrudController::class)
                ->generateUrl()
        );

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
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

        yield MenuItem::linkToCrud(
            "Archers",
            "fa-regular fa-user",
            User::class
        );
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
