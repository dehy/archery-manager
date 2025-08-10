<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UsersTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;

    public function testGetCollection(): void
    {
        UserFactory::createMany(100);

        $response = static::createClient()->request('GET', '/users');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/User',
            '@id' => '/users',
            '@type' => 'Collection',
            'totalItems' => 100,
            'view' => [
                '@id' => '/users?page=1',
                '@type' => 'PartialCollectionView',
                'first' => '/users?page=1',
                'last' => '/users?page=4',
                'next' => '/users?page=2',
            ],
        ]);

        $this->assertCount(30, $response->toArray()['member']);

        $this->assertMatchesResourceCollectionJsonSchema(User::class);
    }

    public function testCreateUser(): void
    {
        $response = static::createClient()->request('POST', '/users', [
            'json' => [
                'email' => 'user@company.com',
                'givenName' => 'Firstname',
                'familyName' => 'Lastname',
                'password' => 'p4ssw0rd',
            ],
            'headers' => [
                'content-type' => 'application/ld+json; charset=utf-8',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/User',
            '@type' => 'User',
            'email' => 'user@company.com',
            'givenName' => 'Firstname',
            'familyName' => 'Lastname',
        ]);
        $this->assertMatchesRegularExpression('~^/users/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testCreateInvalidUser(): void
    {
        static::createClient()->request('POST', '/users', [
            'json' => [
                'email' => 'user@company',
            ],
            'headers' => [
                'content-type' => 'application/ld+json; charset=utf-8',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolationList',
            '@type' => 'ConstraintViolationList',
            'title' => 'An error occurred',
            'description' => 'email: This value is not a valid email address.
password: This value should not be blank.
givenName: This value should not be blank.
familyName: This value should not be blank.',
        ]);
    }

    public function testUpdateUser(): void
    {
        UserFactory::createOne(['email' => 'updated-user@company.com']);

        $client = static::createClient();
        $iri = $this->findIriBy(User::class, ['email' => 'updated-user@company.com']);

        // Use the PATCH method here to do a partial update
        $client->request('PATCH', $iri, [
            'json' => [
                'givenName' => 'Firstname2',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $iri,
            'email' => 'updated-user@company.com',
            'givenName' => 'Firstname2',
        ]);
    }

    public function testDeleteUser(): void
    {
        // Only create the user we need with a given ISBN
        UserFactory::createOne(['email' => 'user-to-delete@company.com']);

        $client = static::createClient();
        $iri = $this->findIriBy(User::class, ['email' => 'user-to-delete@company.com']);

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            // Through the container, you can access all your services from the tests, including the ORM, the mailer, remote API clients...
            static::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user-to-delete@company.com'])
        );
    }
}
