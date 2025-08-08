<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Club;
use App\Factory\ClubFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ClubsTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection(): void
    {
        ClubFactory::createMany(100);

        $response = static::createClient()->request('GET', '/clubs');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/Club',
            '@id' => '/clubs',
            '@type' => 'Collection',
            'totalItems' => 100,
            'view' => [
                '@id' => '/clubs?page=1',
                '@type' => 'PartialCollectionView',
                'first' => '/clubs?page=1',
                'last' => '/clubs?page=4',
                'next' => '/clubs?page=2',
            ],
        ]);

        $this->assertCount(30, $response->toArray()['member']);

        $this->assertMatchesResourceCollectionJsonSchema(Club::class);
    }

    public function testCreateClub(): void
    {
        $response = static::createClient()->request('POST', '/clubs', [
            'json' => [
                'name' => 'Brotherhood',
                'city' => 'Sherwood',
                'email' => 'robin.hood@sher.wood',
                'fftaCode' => '12345678'
            ],
            'headers' => [
                'content-type' => 'application/ld+json; charset=utf-8'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Club',
            '@type' => 'Club',
            'name' => 'Brotherhood',
            'city' => 'Sherwood',
            'email' => 'robin.hood@sher.wood',
            'fftaCode' => '12345678',
        ]);
        $this->assertMatchesRegularExpression('~^/clubs/\d+$~', $response->toArray()['@id']);
        $this->assertMatchesResourceItemJsonSchema(Club::class);
    }

    public function testCreateInvalidClub(): void
    {
        static::createClient()->request('POST', '/clubs', [
            'json' => [
                'email' => 'brotherhood@sher',
            ],
            'headers' => [
                'content-type' => 'application/ld+json; charset=utf-8'
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

        $this->assertJsonContains([
            '@context' => '/contexts/ConstraintViolationList',
            '@type' => 'ConstraintViolationList',
            'title' => 'An error occurred',
            'description' => 'name: This value should not be blank.
city: This value should not be blank.
email: This value is not a valid email address.
fftaCode: This value should not be blank.
fftaCode: This value should have exactly 8 characters.'
        ]);
    }

    public function testUpdateClub(): void
    {
        ClubFactory::createOne(['name' => 'Sisterhood']);

        $client = static::createClient();
        $iri = $this->findIriBy(Club::class, ['name' => 'Sisterhood']);

        // Use the PATCH method here to do a partial update
        $client->request('PATCH', $iri, [
            'json' => [
                'email' => 'sisterhood@sher.wood',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => $iri,
            'name' => 'Sisterhood',
            'email' => 'sisterhood@sher.wood',
        ]);
    }

    public function testDeleteClub(): void
    {
        // Only create the club we need with a given ISBN
        ClubFactory::createOne(['name' => 'Sisterhood']);

        $client = static::createClient();
        $iri = $this->findIriBy(Club::class, ['name' => 'Sisterhood']);

        $client->request('DELETE', $iri);

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            // Through the container, you can access all your services from the tests, including the ORM, the mailer, remote API clients...
            static::getContainer()->get('doctrine')->getRepository(Club::class)->findOneBy(['name' => 'Sisterhood'])
        );
    }
}
