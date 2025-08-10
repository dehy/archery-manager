<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\ClubFactory;
use App\Factory\EventFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EventWorkflowTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;

    public function testCompleteEventWorkflow(): void
    {
        // 1. Create a club
        $club = ClubFactory::createOne([
            'name' => 'Robin Hood Archery Club',
            'fftaCode' => 'RH12345',
        ]);

        // 2. Create an event for the club
        $eventResponse = static::createClient()->request('POST', '/events', [
            'json' => [
                'name' => 'Monthly Tournament',
                'discipline' => 'target',
                'sport' => 'archery',
                'startsAt' => '2025-09-15T10:00:00Z',
                'endsAt' => '2025-09-15T18:00:00Z',
                'club' => '/clubs/'.$club->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $eventId = $eventResponse->toArray()['id'];

        // 3. Create licensees
        $user1 = UserFactory::createOne(['email' => 'archer1@example.com']);
        $user2 = UserFactory::createOne(['email' => 'archer2@example.com']);

        $licensee1Response = static::createClient()->request('POST', '/licensees', [
            'json' => [
                'user' => '/users/'.$user1->getId(),
                'familyName' => 'Hood',
                'givenName' => 'Robin',
                'birthDate' => '1990-01-15',
                'gender' => 'M',
            ],
        ]);

        $licensee2Response = static::createClient()->request('POST', '/licensees', [
            'json' => [
                'user' => '/users/'.$user2->getId(),
                'familyName' => 'Scarlett',
                'givenName' => 'Will',
                'birthDate' => '1992-03-22',
                'gender' => 'M',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $licensee1Id = $licensee1Response->toArray()['id'];
        $licensee2Id = $licensee2Response->toArray()['id'];

        // 4. Register licensees for the event
        static::createClient()->request('POST', '/event_participations', [
            'json' => [
                'event' => '/events/'.$eventId,
                'participant' => '/licensees/'.$licensee1Id,
                'activity' => 'CL',
                'participationState' => 'registered',
            ],
        ]);

        static::createClient()->request('POST', '/event_participations', [
            'json' => [
                'event' => '/events/'.$eventId,
                'participant' => '/licensees/'.$licensee2Id,
                'activity' => 'CO',
                'participationState' => 'registered',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);

        // 5. Verify event has participants
        $eventParticipationsResponse = static::createClient()->request('GET', '/events/'.$eventId.'/participations');

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $eventParticipationsResponse->toArray()['member']);

        // 6. Verify licensee participations
        $licensee1ParticipationsResponse = static::createClient()->request('GET', '/licensees/'.$licensee1Id.'/participations');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $licensee1ParticipationsResponse->toArray()['member']);
    }

    public function testClubEventsRelationship(): void
    {
        $club = ClubFactory::createOne(['name' => 'Test Club']);

        // Create multiple events for the club
        EventFactory::createMany(3, ['club' => $club]);

        // Get club events
        $response = static::createClient()->request('GET', '/clubs/'.$club->getId().'/events');

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $response->toArray()['member']);
    }
}
