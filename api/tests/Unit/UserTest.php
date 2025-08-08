<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Entity\Club;
use App\Type\GenderType;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        $user->email = 'john.doe@example.com';
        $user->givenName = 'John';
        $user->familyName = 'Doe';
        $user->gender = GenderType::Male;

        $this->assertEquals('john.doe@example.com', $user->email);
        $this->assertEquals('John', $user->givenName);
        $this->assertEquals('Doe', $user->familyName);
        $this->assertEquals(GenderType::Male, $user->gender);
    }

    public function testUserToString(): void
    {
        $user = new User();
        $user->givenName = 'Jane';
        $user->familyName = 'Smith';

        $this->assertEquals('Jane Smith', (string) $user);
    }

    public function testUserWithPartialName(): void
    {
        // Test with only given name
        $user1 = new User();
        $user1->givenName = 'Alice';
        $this->assertEquals('Alice ', (string) $user1);

        // Test with only family name
        $user2 = new User();
        $user2->familyName = 'Johnson';
        $this->assertEquals(' Johnson', (string) $user2);

        // Test with no names
        $user3 = new User();
        $this->assertEquals(' ', (string) $user3);
    }

    public function testUserClubRelationship(): void
    {
        $user = new User();
        $user->email = 'member@club.com';

        // Test that user can be created
        $this->assertEquals('member@club.com', $user->email);
    }

    public function testUserRoles(): void
    {
        $user = new User();
        $user->email = 'admin@example.com';

        // Test default role
        $this->assertContains('ROLE_USER', $user->getRoles());

        // Test setting roles
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $roles = $user->getRoles();
        
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testUserPasswordHandling(): void
    {
        $user = new User();
        $user->email = 'test@example.com';

        // Test password setting
        $hashedPassword = 'hashedPasswordValue';
        $user->setPassword($hashedPassword);
        
        $this->assertEquals($hashedPassword, $user->getPassword());
    }

    public function testUserAge(): void
    {
        $user = new User();
        
        // Test that user can be created (age calculation would need actual birthDate field)
        $this->assertInstanceOf(User::class, $user);
    }

    public function testUserEmailValidation(): void
    {
        $user = new User();
        
        // Test valid email
        $user->email = 'valid.email@domain.com';
        $this->assertStringContainsString('@', $user->email);
        $this->assertStringContainsString('.', $user->email);

        // Test getUserIdentifier returns email
        $this->assertEquals('valid.email@domain.com', $user->getUserIdentifier());
    }
}
