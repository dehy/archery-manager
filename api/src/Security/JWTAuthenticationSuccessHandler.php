<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JWTAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Invalid user'], 401);
        }

        // Check if user is verified
        if (!$user->isVerified) {
            return new JsonResponse([
                'message' => 'Please verify your email before logging in'
            ], 401);
        }

        // Create JWT token
        $jwt = $this->jwtManager->create($user);

        // Prepare response data
        $data = [
            'token' => $jwt,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->email,
                'givenName' => $user->givenName,
                'familyName' => $user->familyName,
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified,
            ],
        ];

        // Create response
        $response = new JWTAuthenticationSuccessResponse($jwt, $data);

        // Dispatch event for potential modifications
        $event = new AuthenticationSuccessEvent($data, $user, $response);
        $this->dispatcher->dispatch($event, Events::AUTHENTICATION_SUCCESS);

        return $response;
    }
}
