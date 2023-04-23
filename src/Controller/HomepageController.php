<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\PracticeAdvice;
use App\Entity\Result;
use App\Helper\LicenseeHelper;
use App\Repository\EventRepository;
use App\Repository\PracticeAdviceRepository;
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
    ): Response {
        if ($licenseeHelper->getLicenseeFromSession() === null) {
            return $this->render('homepage/blank_account.html.twig', [
                'user' => $this->getUser(),
            ]);
        }

        /** @var EventRepository $eventRepository */
        $eventRepository = $entityManager->getRepository(Event::class);
        $events = $eventRepository->findNextForLicensee(
            $licenseeHelper->getLicenseeFromSession(),
            5,
        );

        /** @var PracticeAdviceRepository $adviceRepository */
        $adviceRepository = $entityManager->getRepository(PracticeAdvice::class);
        $advices = $adviceRepository->findForLicensee($licenseeHelper->getLicenseeFromSession());

        /** @var ResultRepository $resultRepository */
        $resultRepository = $entityManager->getRepository(Result::class);
        $results = $resultRepository->findLastForLicensee($licenseeHelper->getLicenseeFromSession());

        return $this->render('homepage/index.html.twig', [
            'events' => $events,
            'advices' => $advices,
            'results' => $results,
        ]);
    }
}
