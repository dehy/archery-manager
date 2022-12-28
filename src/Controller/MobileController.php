<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MobileController extends AbstractController
{
    #[Route('/other', name: 'app_mobile_othermenu')]
    public function otherMenu(): Response
    {
        return $this->render('mobile/other_menu.html.twig');
    }
}
