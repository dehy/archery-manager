<?php

declare(strict_types=1);

namespace App\Controller;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    private function renderMarkdownFile(string $filePath, string $title): Response
    {
        $markdownPath = $this->getParameter('kernel.project_dir') . '/' . $filePath;
        
        if (!file_exists($markdownPath)) {
            throw $this->createNotFoundException('Le document demandé n\'existe pas.');
        }

        $markdown = file_get_contents($markdownPath);

        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());

        $converter = new CommonMarkConverter($config, $environment);
        $html = $converter->convert($markdown);

        return $this->render('legal/document.html.twig', [
            'title' => $title,
            'content' => $html,
        ]);
    }

    #[Route('/cgu', name: 'app_legal_cgu')]
    public function cgu(): Response
    {
        return $this->renderMarkdownFile('docs/cgu.md', 'Conditions Générales d\'Utilisation');
    }

    #[Route('/politique-de-confidentialite', name: 'app_legal_privacy')]
    public function privacy(): Response
    {
        return $this->renderMarkdownFile('docs/privacy-policy.md', 'Politique de Confidentialité');
    }
}
