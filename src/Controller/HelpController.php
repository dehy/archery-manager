<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/help')]
#[IsGranted('ROLE_USER')]
class HelpController extends AbstractController
{
    #[Route('/calendar', name: 'app_help_calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        return $this->render('help/calendar.html.twig');
    }
}
