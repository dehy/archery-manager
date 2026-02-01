<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PracticeAdvice;
use App\Helper\LicenseeHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PracticeAdviceController extends AbstractController
{
    public function __construct(private readonly LicenseeHelper $licenseeHelper)
    {
    }

    #[Route('/practice-advices/{advice}', name: 'app_practice_advice_show')]
    public function show(PracticeAdvice $advice): Response
    {
        if ($advice->getLicensee()->getId() !== $this->licenseeHelper->getLicenseeFromSession()->getId()) {
            throw $this->createNotFoundException();
        }

        return $this->render('practice_advice/show.html.twig', [
            'advice' => $advice,
        ]);
    }
}
