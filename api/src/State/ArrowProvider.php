<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Arrow;
use App\Repository\ArrowRepository;

/**
 * @implements ProviderInterface<Arrow>
 */
final readonly class ArrowProvider implements ProviderInterface
{
    public function __construct(
        private ArrowRepository $arrowRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            // Handle subresource: /licensees/{licenseeId}/arrows
            if (isset($uriVariables['licenseeId'])) {
                return $this->arrowRepository->findBy(['owner' => $uriVariables['licenseeId']]);
            }

            return $this->arrowRepository->findAll();
        }

        if ($operation instanceof \ApiPlatform\Metadata\Get) {
            return $this->arrowRepository->find($uriVariables['id']);
        }

        return null;
    }
}
