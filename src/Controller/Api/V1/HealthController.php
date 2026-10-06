<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/api/v1/health', name: 'api_v1_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $response = new JsonResponse(['status' => 'ok', 'version' => 'v1']);
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
