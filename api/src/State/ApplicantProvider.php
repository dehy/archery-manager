<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Applicant;
use App\Repository\ApplicantRepository;

/**
 * @implements ProviderInterface<Applicant>
 */
final readonly class ApplicantProvider implements ProviderInterface
{
    public function __construct(
        private ApplicantRepository $applicantRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            return $this->applicantRepository->findAll();
        }

        if ($operation instanceof \ApiPlatform\Metadata\Get) {
            return $this->applicantRepository->find($uriVariables['id']);
        }

        return null;
    }
}
