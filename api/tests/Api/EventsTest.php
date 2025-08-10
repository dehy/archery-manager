<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Event;
use App\Factory\ClubFactory;
use App\Factory\EventFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EventsTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;

    public function testGetCollection(): void
    {
        // Create some test events
        EventFactory::createMany(5);

        $response = static::createClient()->request('GET', '/events');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Event',
            '@id' => '/events',
            '@type' => 'Collection',
        ]);
        $this->assertCount(5, $response->toArray()['member']);
    }

    public function testCreateEvent(): void
    {
        $club = ClubFactory::createOne();

        $response = static::createClient()->request('POST', '/events', [
            'json' => [
                'name' => 'Test Tournament',
                'discipline' => 'target',
                'sport' => 'archery',
                'startsAt' => '2025-09-15T10:00:00Z',
                'endsAt' => '2025-09-15T18:00:00Z',
                'club' => '/clubs/'.$club->getId(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/contexts/Event',
            '@type' => 'Event',
            'name' => 'Test Tournament',
            'discipline' => 'target',
            'sport' => 'archery',
        ]);
    }

    public function testGetEvent(): void
    {
        $event = EventFactory::createOne([
            'name' => 'Sample Event',
        ]);

        $response = static::createClient()->request('GET', '/events/'.$event->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/contexts/Event',
            '@type' => 'Event',
            'name' => 'Sample Event',
        ]);
    }

    public function testUpdateEvent(): void
    {
        $event = EventFactory::createOne();

        static::createClient()->request('PATCH', '/events/'.$event->getId(), [
            'json' => [
                'name' => 'Updated Event Name',
            ],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => '/events/'.$event->getId(),
            'name' => 'Updated Event Name',
        ]);
    }

    public function testDeleteEvent(): void
    {
        $event = EventFactory::createOne();

        static::createClient()->request('DELETE', '/events/'.$event->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            static::getContainer()->get('doctrine')->getRepository(Event::class)->findOneBy(['id' => $event->getId()])
        );
    }
}
