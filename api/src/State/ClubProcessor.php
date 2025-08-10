<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Club;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProcessorInterface<Club, Club>
 */
final readonly class ClubProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof \ApiPlatform\Metadata\Post) {
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
