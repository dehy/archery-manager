<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
class PdfController
{
    #[Route('/view-pdf/{path}', requirements: ['path' => '[^/][^:]*'])]
    public function show(string $path, Environment $twig): Response
    {
        return new Response(
            $twig->render('pdf.html.twig', [
                'pdf_url' => $path,
            ])
        );
    }
}
