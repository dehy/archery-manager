<?php

namespace App\Controller;

use App\Entity\PracticeAdvice;
use App\Helper\LicenseeHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PracticeAdviceController extends AbstractController
{
    #[Route('/practice-advices/{advice}', name: 'app_practice_advice_show')]
    public function show(PracticeAdvice $advice, LicenseeHelper $licenseeHelper): Response
    {
        if ($advice->getLicensee()->getId() !== $licenseeHelper->getLicenseeFromSession()->getId()) {
            throw $this->createNotFoundException();
        }

        return $this->render('practice_advice/show.html.twig', [
            'advice' => $advice,
        ]);
    }
}
