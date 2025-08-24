<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserRegistrationTest extends AbstractApiTestCase
{
    use ResetDatabase, Factories;

    public function testUserCanRegister(): void
    {
        $client = self::createClient();

        $response = $client->request('POST', '/register', [
            'json' => [
                'email' => 'newuser@example.com',
                'password' => 'SecurePassword123!',
                'givenName' => 'John',
                'familyName' => 'Doe',
                'gender' => 'male'
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        
        $this->assertJsonContains([
            'email' => 'newuser@example.com',
            'givenName' => 'John',
            'familyName' => 'Doe',
            'gender' => 'male',
            'isVerified' => false,
        ]);
        
        $data = $response->toArray();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayNotHasKey('password', $data); // Password should not be exposed
        
        // Verify user was created in database
        $userRepository = self::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'newuser@example.com']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse($user->isVerified);
    }

    public function testUserRegistrationWithDuplicateEmailFails(): void
    {
        $client = self::createClient();

        // Create a user first
        $client->request('POST', '/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'duplicate@example.com',
                'password' => 'SecurePassword123!',
                'givenName' => 'John',
                'familyName' => 'Doe',
                'gender' => 'male'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);

        // Try to create another user with the same email
        $client->request('POST', '/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'duplicate@example.com',
                'password' => 'AnotherPassword123!',
                'givenName' => 'Jane',
                'familyName' => 'Smith',
                'gender' => 'female'
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUserRegistrationWithInvalidDataFails(): void
    {
        $client = self::createClient();

        // Test with missing required fields
        $client->request('POST', '/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'incomplete@example.com',
                // Missing password, givenName, familyName, gender
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);

        // Test with invalid email
        $client->request('POST', '/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'invalid-email',
                'password' => 'SecurePassword123!',
                'givenName' => 'John',
                'familyName' => 'Doe',
                'gender' => 'male'
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUserRegistrationWithWeakPasswordFails(): void
    {
        $client = self::createClient();

        $client->request('POST', '/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'weakpass@example.com',
                'password' => '123', // Too weak
                'givenName' => 'John',
                'familyName' => 'Doe',
                'gender' => 'male'
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
    }
}
