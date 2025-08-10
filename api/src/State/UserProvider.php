<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Custom state provider for User entities
 * Following API Platform 4.0 best practices for separating API logic from persistence.
 */
class UserProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof GetCollection) {
            return $this->userRepository->findAll();
        }

        return $this->userRepository->find($uriVariables['id']);
    }
}
