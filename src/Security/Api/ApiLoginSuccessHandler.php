<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final readonly class ApiLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private ApiTokenManager $tokenManager,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unsupported user.'], Response::HTTP_UNAUTHORIZED);
        }

        $user->resetFailedAttempts();
        $this->entityManager->flush();

        $deviceName = $request->toArray()['device_name'] ?? null;
        $tokens = $this->tokenManager->issue($user, \is_string($deviceName) ? $deviceName : null);

        $response = new JsonResponse($tokens->toArray($this->clock->now()));
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
