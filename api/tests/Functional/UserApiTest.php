<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\AbstractApiTestCase;

class UserApiTest extends AbstractApiTestCase
{
    public function testGetUsersCollection(): void
    {
        // Create some test users
        UserFactory::createMany(3);

        $client = $this->createClientWithAdminCredentials();
        $response = $client->request('GET', '/users');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // Test JSON-LD structure
        $this->assertJsonContains([
            '@context' => '/contexts/User',
            '@type' => 'Collection',
            'totalItems' => 4, // 3 created + 1 admin from auth
        ]);

        // Test that we have the correct number of items
        $this->assertCount(4, $response->toArray()['member']);
    }

    public function testCreateUser(): void
    {
        $client = $this->createClientWithAdminCredentials();

        $response = $client->request('POST', '/users', [
            'json' => [
                'email' => 'new-user@example.com',
                'givenName' => 'New',
                'familyName' => 'User',
                'gender' => 'male',
                'password' => 'SecurePassword123!',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/User',
            '@type' => 'User',
            'email' => 'new-user@example.com',
            'givenName' => 'New',
            'familyName' => 'User',
            'gender' => 'male',
            'isVerified' => false,
        ]);

        $this->assertMatchesRegularExpression('~^/users/[\w-]+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testUpdateUser(): void
    {
        $user = UserFactory::createOne([
            'email' => 'update-test@example.com',
            'givenName' => 'Original',
            'familyName' => 'Name',
            'isVerified' => true,
        ]);

        $client = $this->createClientWithCredentials();
        $iri = $this->findIriBy(User::class, ['email' => 'update-test@example.com']);

        $client->request('PATCH', $iri, [
            'json' => [
                'givenName' => 'Updated',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $iri,
            'email' => 'update-test@example.com',
            'givenName' => 'Updated',
            'familyName' => 'Name',
        ]);
    }

    public function testDeleteUser(): void
    {
        $user = UserFactory::createOne([
            'email' => 'delete-test@example.com',
            'isVerified' => true,
        ]);

        $client = $this->createClientWithCredentials();
        $iri = $this->findIriBy(User::class, ['email' => 'delete-test@example.com']);

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);
        
        // Verify the user was deleted
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(User::class)
                ->findOneBy(['email' => 'delete-test@example.com'])
        );
    }

    public function testCreateUserWithInvalidData(): void
    {
        $client = $this->createClientWithAdminCredentials();

        $response = $client->request('POST', '/users', [
            'json' => [
                'email' => 'invalid-email', // Invalid email format
                'password' => '123', // Too short password
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);

        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolation',
            '@type' => 'ConstraintViolation',
            'status' => 422,
        ]);

        // Verify that we have violations (use false to avoid exception on error responses)
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertIsArray($data['violations']);
        $this->assertNotEmpty($data['violations']);
    }

    public function testGetUserWithoutAuthentication(): void
    {
        UserFactory::createOne(['email' => 'test@example.com']);
        $iri = $this->findIriBy(User::class, ['email' => 'test@example.com']);

        static::createClient()->request('GET', $iri);

        $this->assertResponseStatusCodeSame(401);
    }
}
