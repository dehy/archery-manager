<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Club;
use App\Entity\ContestEvent;
use App\Entity\EventParticipation;
use App\Entity\FreeTrainingEvent;
use App\Entity\Group;
use App\Entity\HobbyContestEvent;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\PracticeAdvice;
use App\Entity\Result;
use App\Entity\SecurityLog;
use App\Entity\TrainingEvent;
use App\Entity\User;
use App\Repository\SecurityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/admin', name: 'admin')]
    #[\Override]
    public function index(): Response
    {
        /** @var SecurityLogRepository $securityLogRepo */
        $securityLogRepo = $this->entityManager->getRepository(SecurityLog::class);
        $this->entityManager->getRepository(User::class);

        // Security statistics
        $failedLoginsLast24h = $securityLogRepo->getFailedLoginCountLast24Hours();
        $lockedAccountsCount = $securityLogRepo->getCurrentlyLockedAccountsCount();
        $mostTargetedAccounts = $securityLogRepo->getMostTargetedAccounts(5);
        $recentSecurityLogs = $securityLogRepo->findRecent(10);

        return $this->render('admin/dashboard.html.twig', [
            'failedLoginsLast24h' => $failedLoginsLast24h,
            'lockedAccountsCount' => $lockedAccountsCount,
            'mostTargetedAccounts' => $mostTargetedAccounts,
            'recentSecurityLogs' => $recentSecurityLogs,
        ]);
    }

    #[\Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Mon Club de Tir à l\'Arc');
    }

    #[\Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard(
            'Tableau de bord',
            'fa-solid fa-gauge-high',
        );

        yield MenuItem::section();

        yield MenuItem::linkToCrud('Clubs', 'fa-regular fa-building', Club::class);

        yield MenuItem::section();

        yield MenuItem::linkToCrud('Comptes', 'fa-regular fa-at', User::class);

        yield MenuItem::linkToCrud(
            'Licenciés',
            'fa-solid fa-user',
            Licensee::class,
        );

        yield MenuItem::linkToCrud(
            'Licences',
            'fa-solid fa-id-card',
            License::class,
        );

        yield MenuItem::section('Entraînements');

        yield MenuItem::linkToCrud(
            'Groupes',
            'fa-solid fa-users',
            Group::class,
        );

        yield MenuItem::linkToCrud(
            'Conseils',
            'fa-regular fa-comment',
            PracticeAdvice::class,
        );

        yield MenuItem::section('Évènements');

        yield MenuItem::linkToRoute(
            'Gestion des événements',
            'fa-solid fa-calendar-plus',
            'app_admin_events_index',
        );

        yield MenuItem::linkToCrud(
            'Entrainements',
            'fa-regular fa-calendar',
            TrainingEvent::class,
        );
        yield MenuItem::linkToCrud(
            'Entrainements Libres',
            'fa-regular fa-calendar',
            FreeTrainingEvent::class,
        );
        yield MenuItem::linkToCrud(
            'Concours',
            'fa-regular fa-calendar',
            ContestEvent::class,
        );
        yield MenuItem::linkToCrud(
            'Challenge 33',
            'fa-regular fa-calendar',
            HobbyContestEvent::class,
        );

        yield MenuItem::linkToCrud(
            'Participations',
            'fa-solid fa-user-check',
            EventParticipation::class,
        );

        yield MenuItem::linkToCrud(
            'Résultats',
            'fa-solid fa-square-poll-vertical',
            Result::class,
        );

        yield MenuItem::section('Technique');

        yield MenuItem::linkToCrud(
            'Journal de sécurité',
            'fa-solid fa-shield-halved',
            SecurityLog::class,
        );

        yield MenuItem::linkToUrl(
            'Audit',
            'fa-solid fa-user-secret',
            '/audit',
        )->setLinkTarget('_blank');

        yield MenuItem::section();

        yield MenuItem::linkToRoute(
            'Retour au site',
            'fa-solid fa-arrow-left',
            'app_homepage',
        );

        yield MenuItem::section();

        yield MenuItem::linkToLogout(
            'Déconnexion',
            'fa-solid fa-arrow-right-from-bracket',
        );
    }
}
