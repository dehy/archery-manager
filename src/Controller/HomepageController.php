<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Result;
use App\Helper\LicenseHelper;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use App\Repository\EventRepository;
use App\Repository\LicenseApplicationRepository;
use App\Repository\ResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(
        EntityManagerInterface $entityManager,
        LicenseeHelper $licenseeHelper,
        LicenseHelper $licenseHelper,
        SeasonHelper $seasonHelper,
        LicenseApplicationRepository $applicationRepository,
    ): Response {
        if (!$licenseeHelper->getLicenseeFromSession() instanceof \App\Entity\Licensee) {
            return $this->render('homepage/blank_account.html.twig', [
                'user' => $this->getUser(),
            ]);
        }

        $licensee = $licenseeHelper->getLicenseeFromSession();
        $currentSeason = $seasonHelper->getSelectedSeason();
        
        // Check if user has a valid license for current season
        $currentLicense = $licensee->getLicenseForSeason($currentSeason);
        
        if ($currentLicense === null) {
            // User has no valid license - show application-focused homepage
            $applications = $applicationRepository->findByLicenseeAndSeason($licensee, $currentSeason);
            
            return $this->render('homepage/no_license.html.twig', [
                'licensee' => $licensee,
                'applications' => $applications,
                'currentSeason' => $currentSeason,
            ]);
        }

        /** @var EventRepository $eventRepository */
        $eventRepository = $entityManager->getRepository(Event::class);
        $events = $eventRepository->findNextForLicensee(
            $licenseeHelper->getLicenseeFromSession(),
            5,
        );

        /** @var ResultRepository $resultRepository */
        $resultRepository = $entityManager->getRepository(Result::class);
        $results = $resultRepository->findLastForLicensee($licenseeHelper->getLicenseeFromSession());

        return $this->render('homepage/index.html.twig', [
            'events' => $events,
            'results' => $results,
        ]);
    }
}
