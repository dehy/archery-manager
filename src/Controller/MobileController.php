<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MobileController extends AbstractController
{
    #[Route('/other', name: 'app_mobile_othermenu')]
    public function otherMenu(): Response
    {
        $user = $this->getUser();

        return $this->render($user instanceof User && $user->hasNewInterface() ? 'mobile/other_menu_next.html.twig' : 'mobile/other_menu.html.twig');
    }
}
