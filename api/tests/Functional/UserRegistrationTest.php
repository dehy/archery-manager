<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UserRegistrationTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clean up existing users before each test
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        $userRepository = $entityManager->getRepository(User::class);
        $users = $userRepository->findAll();
        
        foreach ($users as $user) {
            $entityManager->remove($user);
        }
        
        $entityManager->flush();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
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

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = $response->toArray();
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('newuser@example.com', $data['email']);
        $this->assertEquals('John', $data['givenName']);
        $this->assertEquals('Doe', $data['familyName']);
        $this->assertEquals('male', $data['gender']);
        $this->assertFalse($data['isVerified']); // Should be false until email verification
        $this->assertArrayNotHasKey('password', $data); // Password should not be exposed
        
        // Verify user was created in database
        $userRepository = self::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'newuser@example.com']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse($user->isVerified);
    }

    /**
     * @throws TransportExceptionInterface
     */
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

        // Try to create another user with the same email
        $response = $client->request('POST', '/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'duplicate@example.com',
                'password' => 'AnotherPassword123!',
                'givenName' => 'Jane',
                'familyName' => 'Smith',
                'gender' => 'female'
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testUserRegistrationWithInvalidDataFails(): void
    {
        $client = self::createClient();

        // Test with missing required fields
        $response = $client->request('POST', '/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'incomplete@example.com',
                // Missing password, givenName, familyName, gender
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Test with invalid email
        $response = $client->request('POST', '/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'invalid-email',
                'password' => 'SecurePassword123!',
                'givenName' => 'John',
                'familyName' => 'Doe',
                'gender' => 'male'
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testUserRegistrationWithWeakPasswordFails(): void
    {
        $client = self::createClient();

        $response = $client->request('POST', '/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'weakpass@example.com',
                'password' => '123', // Too weak
                'givenName' => 'John',
                'familyName' => 'Doe',
                'gender' => 'male'
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
