<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use App\Factory\UserFactory;
use App\Tests\AbstractApiTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AuthenticationTest extends AbstractApiTestCase
{
    use ResetDatabase, Factories;

    public function testLoginWithValidCredentials(): void
    {
        // Create a verified test user using the factory
        UserFactory::createOne([
            'email' => 'login-test@example.com',
            'givenName' => 'Login',
            'familyName' => 'Test',
            'isVerified' => true,
            'password' => 'TestPassword123!',
        ]);

        $response = static::createClient()->request('POST', '/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'login-test@example.com',
                'password' => 'TestPassword123!',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        
        $this->assertJsonContains([
            'user' => [
                'email' => 'login-test@example.com',
                'givenName' => 'Login',
                'familyName' => 'Test',
                'isVerified' => true,
                'roles' => ['ROLE_USER'],
            ],
        ]);

        $data = $response->toArray();
        $this->assertArrayHasKey('token', $data);
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
        // Create an unverified test user using the factory
        UserFactory::createOne([
            'email' => 'unverified@example.com',
            'givenName' => 'Unverified',
            'familyName' => 'Test',
            'isVerified' => false,
            'password' => 'TestPassword123!',
        ]);

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
        // Create a verified test user using the factory
        UserFactory::createOne([
            'email' => 'token-test@example.com',
            'givenName' => 'Token',
            'familyName' => 'Test',
            'isVerified' => true,
            'password' => 'TestPassword123!',
        ]);

        // Login to get token
        $loginResponse = static::createClient()->request('POST', '/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'token-test@example.com',
                'password' => 'TestPassword123!',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $loginData = $loginResponse->toArray();
        $token = $loginData['token'];

        // Access protected endpoint with token
        $client = static::createClient();
        $response = $client->request('GET', '/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'email' => 'token-test@example.com',
            'givenName' => 'Token',
            'familyName' => 'Test',
            'isVerified' => true,
        ]);
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
        // Create a verified test user using the factory
        UserFactory::createOne([
            'email' => 'refresh-test@example.com',
            'givenName' => 'Refresh',
            'familyName' => 'Test',
            'isVerified' => true,
            'password' => 'TestPassword123!',
        ]);

        // Login to get initial token
        $loginResponse = static::createClient()->request('POST', '/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'refresh-test@example.com',
                'password' => 'TestPassword123!',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $loginData = $loginResponse->toArray();
        $originalToken = $loginData['token'];

        // Refresh the token
        $client = static::createClient();
        $response = $client->request('POST', '/refresh-token', [
            'headers' => [
                'Authorization' => 'Bearer ' . $originalToken,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [], // Empty body since TokenRefresh DTO is empty
        ]);

        $this->assertResponseIsSuccessful();
        
        $data = $response->toArray();
        $this->assertArrayHasKey('token', $data);
        $this->assertIsString($data['token']);
        $this->assertNotEmpty($data['token']);
    }
}
