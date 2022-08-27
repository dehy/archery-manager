<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\PracticeAdvice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route("/", name: "app_homepage")]
    public function index(EntityManagerInterface $entityManager): Response {
        $eventRepository = $entityManager->getRepository(Event::class);
        $events = $eventRepository->findBy([], null, 5);

        $adviceRepository = $entityManager->getRepository(
            PracticeAdvice::class
        );
        $advices = $adviceRepository->findBy([
            "licensee" => $this->getUser()
                ->getLicensees()
                ->first(),
        ]);

        return $this->render("homepage/index.html.twig", [
            "events" => $events,
            "advices" => $advices,
        ]);
    }
}
