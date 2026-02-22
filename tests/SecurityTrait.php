<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\User;
use App\Security\TokenStorageDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

trait SecurityTrait
{
    protected static array $users = [];

    protected function login(UserInterface $user): ?UserInterface
    {
        $tokenStorage = self::getContainer()->get('security.token_storage');

        /** @var TokenStorageDecorator $tokenStorage */
        if ($tokenStorage instanceof TokenStorageDecorator) {
            $tokenStorage->setUser($user);
        } else {
            $tokenStorage->setToken(
                TokenStorageDecorator::getNewToken($user)
            );
        }

        return $tokenStorage->getToken()->getUser();
    }

    protected function loginAsRole(string $role = 'ROLE_USER', bool $userFromDatabase = false): ?UserInterface
    {
        $user = $this->getUser($role, $userFromDatabase);

        return $this->login($user);
    }

    public function logout(): void
    {
        /** @var TokenStorageDecorator $tokenStorage */
        $tokenStorage = self::getContainer()->get('security.token_storage');

        $tokenStorage->setToken();
    }

    protected function getUser(string $role = 'ROLE_USER', bool $userFromDatabase = false): User
    {
        if (empty(self::$users[$role])) {
            self::$users[$role] = $userFromDatabase
                ? ($this->getFirstUserByRole($role) ?: $this->createNewUser($role, $userFromDatabase))
                : $this->createNewUser($role, $userFromDatabase);
        }

        return self::$users[$role];
    }

    protected function getFirstUserByRole(string $role = 'ROLE_USER'): ?User
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.default_entity_manager');

        return $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter(':role', '%'.$role.'%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    protected function createNewUser(string $role = 'ROLE_USE', bool $persist = false): User
    {
        $user = new User()
            ->setRoles([$role])
            ->setEmail(\sprintf('test_%s@test.com', strtolower($role)))
            ->setPassword('test')
            ->setFirstName('Test')
            ->setLastName('Test');

        if ($persist) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = self::getContainer()->get('doctrine.orm.default_entity_manager');

            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $user;
    }
}
