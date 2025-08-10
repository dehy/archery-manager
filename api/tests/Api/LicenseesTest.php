<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\LicenseeFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LicenseesTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;

    public function testGetCollection(): void
    {
        LicenseeFactory::createMany(10);

        $response = static::createClient()->request('GET', '/licensees');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Licensee',
            '@id' => '/licensees',
            '@type' => 'Collection',
        ]);
        $this->assertCount(10, $response->toArray()['member']);
    }

    public function testCreateLicensee(): void
    {
        $user = UserFactory::createOne();

        $response = static::createClient()->request('POST', '/licensees', [
            'json' => [
                'user' => '/users/'.$user->getId(),
                'familyName' => 'Doe',
                'givenName' => 'John',
                'birthDate' => '1990-01-15',
                'gender' => 'M',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Licensee',
            '@type' => 'Licensee',
            'familyName' => 'Doe',
            'givenName' => 'John',
            'gender' => 'M',
        ]);
    }

    public function testGetLicensee(): void
    {
        $licensee = LicenseeFactory::createOne([
            'familyName' => 'Smith',
            'givenName' => 'Jane',
        ]);

        $response = static::createClient()->request('GET', '/licensees/'.$licensee->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Licensee',
            '@type' => 'Licensee',
            'familyName' => 'Smith',
            'givenName' => 'Jane',
        ]);
    }

    public function testSearchLicensees(): void
    {
        LicenseeFactory::createOne(['familyName' => 'Archer', 'givenName' => 'Robin']);
        LicenseeFactory::createOne(['familyName' => 'Bowman', 'givenName' => 'William']);
        LicenseeFactory::createOne(['familyName' => 'Target', 'givenName' => 'Annie']);

        $response = static::createClient()->request('GET', '/licensees?familyName=Archer');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $response->toArray()['member']);
        $this->assertJsonContains([
            'member' => [
                [
                    'familyName' => 'Archer',
                    'givenName' => 'Robin',
                ],
            ],
        ]);
    }
}
