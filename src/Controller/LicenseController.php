<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LicenseController extends BaseController
{
    #[Route('/licenses/application', name: 'app_license_application')]
    public function application(): Response
    {
        return $this->render('license/application.html.twig');
    }
}
