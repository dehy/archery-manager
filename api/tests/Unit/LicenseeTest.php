<?php

namespace App\Tests\Unit;

use App\Entity\Licensee;
use App\Entity\User;
use App\Type\GenderType;
use App\Type\LicenseAgeCategoryType;
use PHPUnit\Framework\TestCase;

class LicenseeTest extends TestCase
{
    public function testLicenseeCreation(): void
    {
        $licensee = new Licensee();
        $licensee->familyName = 'Doe';
        $licensee->givenName = 'John';
        $licensee->birthDate = new \DateTimeImmutable('1990-01-15');
        $licensee->gender = GenderType::Male;

        $this->assertEquals('Doe', $licensee->familyName);
        $this->assertEquals('John', $licensee->givenName);
        $this->assertEquals(GenderType::Male, $licensee->gender);
        $this->assertInstanceOf(\DateTimeImmutable::class, $licensee->birthDate);
    }

    public function testLicenseeToString(): void
    {
        $licensee = new Licensee();
        $licensee->familyName = 'Smith';
        $licensee->givenName = 'Jane';

        $this->assertEquals('Jane Smith', (string) $licensee);
    }

    public function testLicenseeWithUser(): void
    {
        $user = new User();
        $user->email = 'john.doe@example.com';

        $licensee = new Licensee();
        $licensee->user = $user;
        $licensee->familyName = 'Doe';
        $licensee->givenName = 'John';

        $this->assertEquals($user, $licensee->user);
        $this->assertEquals('john.doe@example.com', $licensee->user->email);
    }

    public function testLicenseeCollections(): void
    {
        $licensee = new Licensee();

        // Test that collections are initialized
        $this->assertNotNull($licensee->bows);
        $this->assertNotNull($licensee->arrows);
        $this->assertNotNull($licensee->eventParticipations);
        $this->assertNotNull($licensee->practiceAdvices);
        $this->assertNotNull($licensee->givenPracticeAdvices);
        $this->assertNotNull($licensee->results);
        $this->assertNotNull($licensee->groups);
        
        // Test that collections are empty initially
        $this->assertCount(0, $licensee->bows);
        $this->assertCount(0, $licensee->arrows);
        $this->assertCount(0, $licensee->eventParticipations);
    }
}
