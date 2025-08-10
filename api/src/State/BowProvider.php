<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Bow;
use App\Repository\BowRepository;

/**
 * @implements ProviderInterface<Bow>
 */
final readonly class BowProvider implements ProviderInterface
{
    public function __construct(
        private BowRepository $bowRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            // Handle subresource: /licensees/{licenseeId}/bows
            if (isset($uriVariables['licenseeId'])) {
                return $this->bowRepository->findBy(['owner' => $uriVariables['licenseeId']]);
            }

            return $this->bowRepository->findAll();
        }

        if ($operation instanceof \ApiPlatform\Metadata\Get) {
            return $this->bowRepository->find($uriVariables['id']);
        }

        return null;
    }
}
