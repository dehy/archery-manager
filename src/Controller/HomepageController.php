<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Result;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use App\Repository\ClubApplicationRepository;
use App\Repository\EventRepository;
use App\Repository\ResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomepageController extends AbstractController
{
    public function __construct(private readonly LicenseeHelper $licenseeHelper, private readonly SeasonHelper $seasonHelper, private readonly ClubApplicationRepository $applicationRepository)
    {
    }

    #[Route('/', name: 'app_homepage')]
    public function index(
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->licenseeHelper->getLicenseeFromSession() instanceof \App\Entity\Licensee) {
            return $this->render('homepage/blank_account.html.twig', [
                'user' => $this->getUser(),
            ]);
        }

        $licensee = $this->licenseeHelper->getLicenseeFromSession();
        $currentSeason = $this->seasonHelper->getSelectedSeason();

        // Check if user has a valid license for current season
        $currentLicense = $licensee->getLicenseForSeason($currentSeason);

        if (!$currentLicense instanceof \App\Entity\License) {
            // User has no valid license - show application-focused homepage
            $applications = $this->applicationRepository->findByLicenseeAndSeason($licensee, $currentSeason);

            return $this->render('homepage/no_license.html.twig', [
                'licensee' => $licensee,
                'applications' => $applications,
                'currentSeason' => $currentSeason,
            ]);
        }

        /** @var EventRepository $eventRepository */
        $eventRepository = $entityManager->getRepository(Event::class);
        $events = $eventRepository->findNextForLicensee(
            $this->licenseeHelper->getLicenseeFromSession(),
            5,
        );

        /** @var ResultRepository $resultRepository */
        $resultRepository = $entityManager->getRepository(Result::class);
        $results = $resultRepository->findLastForLicensee($this->licenseeHelper->getLicenseeFromSession());

        return $this->render('homepage/index.html.twig', [
            'events' => $events,
            'results' => $results,
        ]);
    }
}
