<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Club;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testFindByClubAndRoleUser()
    {
        $club = $this->entityManager
            ->getRepository(Club::class)
            ->findOneBy(['name' => 'Les Archers de Guyenne']);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findByClubAndRole($club, 'ROLE_USER');

        self::assertCount(11, $users);
    }

    public function testFindByClubAndRoleClubAdmin()
    {
        $club = $this->entityManager
            ->getRepository(Club::class)
            ->findOneBy(['name' => 'Les Archers de Guyenne']);
        self::assertNotNull($club);
        self::assertSame('Les Archers de Guyenne', $club->getName());

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findByClubAndRole($club, 'ROLE_CLUB_ADMIN');

        self::assertCount(1, $users);
        self::assertSame('clubadmin@ladg.com', $users[0]->getEmail());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
