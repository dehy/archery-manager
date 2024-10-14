<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Club;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRepositoryTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testFindByClubAndRoleUser(): void
    {
        $club = $this->entityManager
            ->getRepository(Club::class)
            ->findOneBy(['name' => 'Les Archers de Guyenne']);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findByClubAndRole($club, 'ROLE_USER');

        $this->assertCount(11, $users);
    }

    public function testFindByClubAndRoleClubAdmin(): void
    {
        $club = $this->entityManager
            ->getRepository(Club::class)
            ->findOneBy(['name' => 'Les Archers de Guyenne']);
        $this->assertNotNull($club);
        $this->assertSame('Les Archers de Guyenne', $club->getName());

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findByClubAndRole($club, 'ROLE_CLUB_ADMIN');

        $this->assertCount(1, $users);
        $this->assertSame('clubadmin@ladg.com', $users[0]->getEmail());
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
