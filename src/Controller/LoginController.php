<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\FriendlyCaptchaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    public function __construct(
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly RequestStack $requestStack,
        private readonly FriendlyCaptchaService $captchaService,
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function index(): Response
    {
        // get the login error if there is one
        $error = $this->authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $this->authenticationUtils->getLastUsername();

        // Get failed login count from session
        $session = $this->requestStack->getSession();
        $failedLoginCount = $session->get('failed_login_count', 0);

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'failed_login_count' => $failedLoginCount,
            'captcha_site_key' => $this->captchaService->getSiteKey(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('Should be intercepted by firewall');
    }
}
