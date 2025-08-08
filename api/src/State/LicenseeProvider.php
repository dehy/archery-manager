<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Licensee;
use App\Repository\LicenseeRepository;

/**
 * @implements ProviderInterface<Licensee>
 */
final readonly class LicenseeProvider implements ProviderInterface
{
    public function __construct(
        private LicenseeRepository $licenseeRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            return $this->licenseeRepository->findAll();
        }

        if ($operation instanceof \ApiPlatform\Metadata\Get) {
            return $this->licenseeRepository->find($uriVariables['id']);
        }

        return null;
    }
}
