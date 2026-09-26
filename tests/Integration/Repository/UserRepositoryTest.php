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
    private const string CLUB_NAME = 'Les Archers de Guyenne';

    private const string CLUB_ADMIN_EMAIL = 'clubadmin@ladg.com';

    private const string GLOBAL_ADMIN_EMAIL = 'admin@acme.org';

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
            ->findOneBy(['name' => self::CLUB_NAME]);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->assertInstanceOf(Club::class, $club);
        $users = $userRepository->findByClubAndRole($club, 'ROLE_USER');

        $this->assertCount(12, $users);
    }

    public function testFindByClubAndRoleClubAdmin(): void
    {
        $club = $this->entityManager
            ->getRepository(Club::class)
            ->findOneBy(['name' => self::CLUB_NAME]);
        $this->assertInstanceOf(Club::class, $club);
        $this->assertSame(self::CLUB_NAME, $club->getName());

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findByClubAndRole($club, 'ROLE_CLUB_ADMIN');

        $this->assertCount(1, $users);
        $this->assertSame(self::CLUB_ADMIN_EMAIL, $users[0]->getEmail());
    }

    public function testFindByEmailsReturnsRequestedUsers(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $users = $userRepository->findByEmails([
            self::CLUB_ADMIN_EMAIL,
            self::GLOBAL_ADMIN_EMAIL,
            self::CLUB_ADMIN_EMAIL,
        ]);

        $this->assertEqualsCanonicalizing(
            [self::CLUB_ADMIN_EMAIL, self::GLOBAL_ADMIN_EMAIL],
            array_map(static fn (User $user): string => (string) $user->getEmail(), $users),
        );
    }

    public function testFindByEmailsReturnsEmptyArrayForEmptyEmails(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $this->assertSame([], $userRepository->findByEmails([]));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
