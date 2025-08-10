<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Applicant;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProcessorInterface<Applicant, Applicant>
 */
final readonly class ApplicantProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof \ApiPlatform\Metadata\Post) {
            // Auto-generate application date if not set
            if ($data instanceof Applicant && !$data->applicationDate) {
                $data->applicationDate = new \DateTimeImmutable();
            }

            $this->entityManager->persist($data);
            $this->entityManager->flush();

            return $data;
        }

        if ($operation instanceof \ApiPlatform\Metadata\Patch) {
            $this->entityManager->flush();

            return $data;
        }

        if ($operation instanceof \ApiPlatform\Metadata\Delete) {
            $this->entityManager->remove($data);
            $this->entityManager->flush();

            return null;
        }

        return $data;
    }
}
