<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route("/my-account", name: "app_user_account", methods: ["GET"])]
    public function account(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render("user/account.html.twig", [
            "user" => $user,
        ]);
    }
}
