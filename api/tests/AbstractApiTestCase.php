<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class AbstractApiTestCase extends ApiTestCase
{
    use ResetDatabase, Factories;

    protected function createClientWithCredentials(?string $token = null): Client
    {
        $token = $token ?: $this->getAdminToken();

        return static::createClient([], ['headers' => ['authorization' => 'Bearer ' . $token]]);
    }

    /**
     * Create a JWT token for an admin user.
     */
    protected function getAdminToken(): string
    {
        // Create an admin user for testing
        UserFactory::createOne([
            'email' => 'admin@example.com',
            'password' => 'TestPassword123!',
            'isVerified' => true,
            'roles' => ['ROLE_ADMIN'],
        ]);

        $response = static::createClient()->request('POST', '/login', [
            'json' => [
                'email' => 'admin@example.com',
                'password' => 'TestPassword123!',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        return $data['token'];
    }

    /**
     * Create a JWT token for a regular user.
     */
    protected function getUserToken(): string
    {
        // Create a regular user for testing
        UserFactory::createOne([
            'email' => 'user@example.com',
            'password' => 'TestPassword123!',
            'isVerified' => true,
            'roles' => ['ROLE_USER'],
        ]);

        $response = static::createClient()->request('POST', '/login', [
            'json' => [
                'email' => 'user@example.com',
                'password' => 'TestPassword123!',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        return $data['token'];
    }

    /**
     * Create a client with admin credentials.
     */
    protected function createClientWithAdminCredentials(): Client
    {
        return $this->createClientWithCredentials($this->getAdminToken());
    }

    /**
     * Create a client with user credentials.
     */
    protected function createClientWithUserCredentials(): Client
    {
        return $this->createClientWithCredentials($this->getUserToken());
    }
}
