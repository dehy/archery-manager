<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\DBAL\Types\GenderType;
use App\Entity\Licensee;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testUserCanBeCreated(): void
    {
        $user = new User();

        $this->assertNull($user->getId());
        $this->assertCount(0, $user->getLicensees());
    }

    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testSetAndGetPassword(): void
    {
        $user = new User();
        $user->setPassword('hashedPassword123');

        $this->assertSame('hashedPassword123', $user->getPassword());
    }

    public function testSetAndGetGender(): void
    {
        $user = new User();
        $user->setGender(GenderType::MALE);

        $this->assertSame(GenderType::MALE, $user->getGender());
    }

    public function testSetAndGetFirstname(): void
    {
        $user = new User();
        $user->setFirstname('John');

        $this->assertSame('John', $user->getFirstname());
    }

    public function testSetAndGetLastname(): void
    {
        $user = new User();
        $user->setLastname('Doe');

        $this->assertSame('Doe', $user->getLastname());
    }

    public function testGetFullname(): void
    {
        $user = new User();
        $user->setFirstname('John')
            ->setLastname('Doe');

        $this->assertSame('John Doe', $user->getFullname());
    }

    public function testToStringReturnsFullname(): void
    {
        $user = new User();
        $user->setFirstname('Jane')
            ->setLastname('Smith');

        $this->assertSame('Jane Smith', (string) $user);
    }

    public function testSetAndGetPhoneNumber(): void
    {
        $user = new User();
        $user->setPhoneNumber('0123456789');

        $this->assertSame('0123456789', $user->getPhoneNumber());
    }

    public function testPhoneNumberCanBeNull(): void
    {
        $user = new User();
        $user->setPhoneNumber(null);

        $this->assertNull($user->getPhoneNumber());
    }

    public function testRolesAlwaysIncludesRoleUser(): void
    {
        $user = new User();

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    public function testSetAndGetRoles(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_COACH']);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_COACH', $roles);
    }

    public function testRolesAreUnique(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_ADMIN']);

        $roles = $user->getRoles();

        $this->assertCount(2, $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testEraseCredentials(): void
    {
        $user = new User();

        // Should not throw exception
        $user->eraseCredentials();

        $this->assertTrue(true);
    }

    public function testSetAndGetIsVerified(): void
    {
        $user = new User();

        $this->assertFalse($user->isIsVerified());

        $user->setIsVerified(true);

        $this->assertTrue($user->isIsVerified());
    }

    public function testSetAndGetDiscordId(): void
    {
        $user = new User();
        $user->setDiscordId('123456789');

        $this->assertSame('123456789', $user->getDiscordId());
    }

    public function testSetAndGetDiscordAccessToken(): void
    {
        $user = new User();
        $user->setDiscordAccessToken('token123');

        $this->assertSame('token123', $user->getDiscordAccessToken());
    }

    public function testAddLicensee(): void
    {
        $user = new User();
        $licensee = $this->createMock(Licensee::class);

        $licensee->expects($this->once())
            ->method('setUser')
            ->with($user);

        $user->addLicensee($licensee);

        $this->assertCount(1, $user->getLicensees());
        $this->assertTrue($user->getLicensees()->contains($licensee));
    }

    public function testAddLicenseeDoesNotAddDuplicate(): void
    {
        $user = new User();
        $licensee = $this->createMock(Licensee::class);

        $licensee->expects($this->once())
            ->method('setUser')
            ->with($user);

        $user->addLicensee($licensee);
        $user->addLicensee($licensee);

        $this->assertCount(1, $user->getLicensees());
    }

    public function testRemoveLicensee(): void
    {
        $user = new User();
        $licensee = $this->createMock(Licensee::class);

        $licensee->method('setUser');
        $licensee->method('getUser')->willReturn($user);

        $user->addLicensee($licensee);
        $this->assertCount(1, $user->getLicensees());

        $licensee->expects($this->once())
            ->method('setUser')
            ->with(null);

        $user->removeLicensee($licensee);

        $this->assertCount(0, $user->getLicensees());
    }

    public function testHasLicenseeWithCode(): void
    {
        $user = new User();
        $licensee = $this->createMock(Licensee::class);

        $licensee->method('setUser');
        $licensee->method('getFftaMemberCode')->willReturn('12345678');

        $user->addLicensee($licensee);

        $this->assertTrue($user->hasLicenseeWithCode('12345678'));
        $this->assertFalse($user->hasLicenseeWithCode('87654321'));
    }

    public function testGetLicenseeWithCode(): void
    {
        $user = new User();
        $licensee1 = $this->createMock(Licensee::class);
        $licensee2 = $this->createMock(Licensee::class);

        $licensee1->method('setUser');
        $licensee1->method('getFftaMemberCode')->willReturn('12345678');

        $licensee2->method('setUser');
        $licensee2->method('getFftaMemberCode')->willReturn('87654321');

        $user->addLicensee($licensee1);
        $user->addLicensee($licensee2);

        $this->assertSame($licensee1, $user->getLicenseeWithCode('12345678'));
        $this->assertSame($licensee2, $user->getLicenseeWithCode('87654321'));
        $this->assertNull($user->getLicenseeWithCode('00000000'));
    }

    public function testSerializeAndUnserialize(): void
    {
        $user = new User();
        $user->setEmail('test@example.com')
            ->setPassword('hashedPassword')
            ->setRoles(['ROLE_ADMIN']);

        $serialized = $user->__serialize();

        $this->assertIsArray($serialized);
        $this->assertArrayHasKey('id', $serialized);
        $this->assertArrayHasKey('email', $serialized);
        $this->assertArrayHasKey('roles', $serialized);
        $this->assertArrayHasKey('password', $serialized);
        $this->assertSame('test@example.com', $serialized['email']);
        $this->assertSame('hashedPassword', $serialized['password']);

        $newUser = new User();
        $newUser->__unserialize($serialized);

        $this->assertSame('test@example.com', $newUser->getEmail());
        $this->assertSame('hashedPassword', $newUser->getPassword());
        $this->assertContains('ROLE_ADMIN', $newUser->getRoles());
    }

    public function testFluentInterface(): void
    {
        $user = new User();

        $result = $user
            ->setEmail('test@example.com')
            ->setPassword('password')
            ->setGender(GenderType::FEMALE)
            ->setFirstname('Jane')
            ->setLastname('Doe')
            ->setPhoneNumber('0123456789')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true)
            ->setDiscordId('123')
            ->setDiscordAccessToken('token');

        $this->assertSame($user, $result);
    }
}
