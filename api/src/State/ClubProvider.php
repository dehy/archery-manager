<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Club;
use App\Repository\ClubRepository;

/**
 * @implements ProviderInterface<Club>
 */
final readonly class ClubProvider implements ProviderInterface
{
    public function __construct(
        private ClubRepository $clubRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            return $this->clubRepository->findAll();
        }

        if ($operation instanceof \ApiPlatform\Metadata\Get) {
            return $this->clubRepository->find($uriVariables['id']);
        }

        return null;
    }
}
