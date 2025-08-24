<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        // Clean up any existing test data
        $this->cleanupTestData();
    }

    private function cleanupTestData(): void
    {
        $emails = [
            'login-test@example.com',
            'token-test@example.com',
            'refresh-test@example.com',
            'unverified@example.com',
        ];
        
        foreach ($emails as $email) {
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $email]);
            if ($user) {
                // Ensure the entity is managed
                if (!$this->entityManager->contains($user)) {
                    $user = $this->entityManager->find(User::class, $user->getId());
                }
                if ($user) {
                    $this->entityManager->remove($user);
                }
            }
        }
        
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }

    public function testLoginWithValidCredentials(): void
    {
        // Create a verified test user first
        $user = new User();
        $user->email = 'login-test@example.com';
        $user->givenName = 'Login';
        $user->familyName = 'Test';
        $user->isVerified = true; // User must be verified to login
        $user->setPassword($this->passwordHasher->hashPassword($user, 'TestPassword123!'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $response = static::createClient()->request('POST', '/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'login-test@example.com',
                'password' => 'TestPassword123!',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();

        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals('login-test@example.com', $data['user']['email']);
        $this->assertEquals('Login', $data['user']['givenName']);
        $this->assertEquals('Test', $data['user']['familyName']);
        $this->assertTrue($data['user']['isVerified']);
        $this->assertEquals(['ROLE_USER'], $data['user']['roles']);

        // Verify the token is valid
        $this->assertIsString($data['token']);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        static::createClient()->request('POST', '/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'nonexistent@example.com',
                'password' => 'WrongPassword',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginWithUnverifiedUser(): void
    {
        // Create an unverified test user
        $user = new User();
        $user->email = 'unverified@example.com';
        $user->givenName = 'Unverified';
        $user->familyName = 'Test';
        $user->isVerified = false; // User is not verified
        $user->setPassword($this->passwordHasher->hashPassword($user, 'TestPassword123!'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        static::createClient()->request('POST', '/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'unverified@example.com',
                'password' => 'TestPassword123!',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAccessProtectedEndpointWithValidToken(): void
    {
        // Create a verified test user
        $user = new User();
        $user->email = 'token-test@example.com';
        $user->givenName = 'Token';
        $user->familyName = 'Test';
        $user->isVerified = true;
        $user->setPassword($this->passwordHasher->hashPassword($user, 'TestPassword123!'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Login to get token
        $loginResponse = static::createClient()->request('POST', '/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'token-test@example.com',
                'password' => 'TestPassword123!',
            ],
        ]);

        $loginData = $loginResponse->toArray();
        $token = $loginData['token'];

        // Access protected endpoint with token
        $response = static::createClient()->request('GET', '/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();

        $this->assertEquals('token-test@example.com', $data['email']);
        $this->assertEquals('Token', $data['givenName']);
        $this->assertEquals('Test', $data['familyName']);
        $this->assertTrue($data['isVerified']);
    }

    public function testAccessProtectedEndpointWithoutToken(): void
    {
        static::createClient()->request('GET', '/me', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRefreshToken(): void
    {
        // Create a verified test user
        $user = new User();
        $user->email = 'refresh-test@example.com';
        $user->givenName = 'Refresh';
        $user->familyName = 'Test';
        $user->isVerified = true;
        $user->setPassword($this->passwordHasher->hashPassword($user, 'TestPassword123!'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Login to get initial token
        $loginResponse = static::createClient()->request('POST', '/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'refresh-test@example.com',
                'password' => 'TestPassword123!',
            ],
        ]);

        $loginData = $loginResponse->toArray();
        $originalToken = $loginData['token'];

        // Refresh the token
        $response = static::createClient()->request('POST', '/refresh-token', [
            'headers' => [
                'Authorization' => 'Bearer ' . $originalToken,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [], // Empty body since TokenRefresh DTO is empty
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();

        $this->assertArrayHasKey('token', $data);
        $newToken = $data['token'];

        // Verify we got a token (may be same as original due to same timestamp)
        $this->assertIsString($newToken);
        $this->assertNotEmpty($newToken);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanupTestData();
        parent::tearDown();
    }
}
