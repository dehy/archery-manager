<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<User>
 */
final readonly class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof \ApiPlatform\Metadata\Post) {
            // Hash password before persisting
            if ($data instanceof User && $data->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword($data, $data->getPlainPassword());
                $data->setPassword($hashedPassword);
                $data->eraseCredentials(); // Clear plain password
            }

            $this->entityManager->persist($data);
            $this->entityManager->flush();

            return $data;
        }

        if ($operation instanceof \ApiPlatform\Metadata\Patch) {
            // Hash password if it's being updated
            if ($data instanceof User && $data->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword($data, $data->getPlainPassword());
                $data->setPassword($hashedPassword);
                $data->eraseCredentials(); // Clear plain password
            }

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
