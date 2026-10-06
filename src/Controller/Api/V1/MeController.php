<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Entity\User;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class MeController
{
    public function __construct(
        private LicenseeHelper $licenseeHelper,
        private SeasonHelper $seasonHelper,
    ) {
    }

    #[Route('/api/v1/me', name: 'api_v1_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $licensees = [];
        foreach ($user->getLicensees() as $licensee) {
            $licensees[] = [
                'ffta_member_code' => $licensee->getFftaMemberCode(),
                'firstname' => $licensee->getFirstname(),
                'lastname' => $licensee->getLastname(),
            ];
        }

        $response = new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
            ],
            'licensees' => $licensees,
            'context' => [
                'licensee' => $this->licenseeHelper->getLicenseeFromSession()?->getFftaMemberCode(),
                'season' => $this->seasonHelper->getSelectedSeason(),
            ],
        ]);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
