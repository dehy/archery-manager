<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\EventFactory;
use App\Factory\EventParticipationFactory;
use App\Factory\LicenseeFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EventParticipationsTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;

    public function testCreateEventParticipation(): void
    {
        $event = EventFactory::createOne();
        $licensee = LicenseeFactory::createOne();

        $response = static::createClient()->request('POST', '/event_participations', [
            'json' => [
                'event' => '/events/'.$event->getId(),
                'participant' => '/licensees/'.$licensee->getId(),
                'activity' => 'CL',
                'participationState' => 'registered',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/contexts/EventParticipation',
            '@type' => 'EventParticipation',
            'activity' => 'CL',
            'participationState' => 'registered',
        ]);
    }

    public function testGetEventParticipations(): void
    {
        $event = EventFactory::createOne();
        EventParticipationFactory::createMany(3, ['event' => $event]);

        $response = static::createClient()->request('GET', '/events/'.$event->getId().'/participations');

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $response->toArray()['member']);
    }

    public function testGetLicenseeParticipations(): void
    {
        $licensee = LicenseeFactory::createOne();
        EventParticipationFactory::createMany(2, ['participant' => $licensee]);

        $response = static::createClient()->request('GET', '/licensees/'.$licensee->getId().'/participations');

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $response->toArray()['member']);
    }
}
