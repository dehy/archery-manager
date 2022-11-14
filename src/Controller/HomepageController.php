<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\PracticeAdvice;
use App\Entity\Result;
use App\Helper\LicenseeHelper;
use App\Repository\EventRepository;
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
        /** @var EventRepository $eventRepository */
        $eventRepository = $entityManager->getRepository(Event::class);
        $events = $eventRepository->findNextForLicensee(
            $licenseeHelper->getLicenseeFromSession(),
            5,
        );

        $adviceRepository = $entityManager->getRepository(PracticeAdvice::class);
        $advices = $adviceRepository->findBy([
            'licensee' => $licenseeHelper->getLicenseeFromSession(),
        ]);

        $resultRepository = $entityManager->getRepository(Result::class);
        $results = $resultRepository->findLastForLicensee($licenseeHelper->getLicenseeFromSession());

        $resultsByTypeAndDiscipline = [];
        foreach ($results as $result) {
            $resultsByTypeAndDiscipline[$result->getEvent()->getType()][$result->getEvent()->getDiscipline()] = $result;
        }

        return $this->render('homepage/index.html.twig', [
            'events' => $events,
            'advices' => $advices,
            'results' => $results,
        ]);
    }
}
