<?php

declare(strict_types=1);

namespace App\Tests\application;

use App\Repository\EventRepository;

/**
 * @internal
 */
final class SmokeTest extends LoggedInTestCase
{
    /**
     * @dataProvider publicUrlsProvider
     */
    public function testPublicUrls(string $url): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
    }

    public function publicUrlsProvider(): \Iterator
    {
        yield ['/login'];
        yield ['/pre-inscription'];
        yield ['/pre-inscription/merci'];
        yield ['/pre-inscription-renouvellement'];
        yield ['/pre-inscription-renouvellement/merci'];
    }

    /**
     * @dataProvider adminPrivateUrlsProvider
     */
    public function testPrivateUrlsAsAdmin(string $url): void
    {
        $client = static::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
    }

    public function adminPrivateUrlsProvider(): \Iterator
    {
        yield ['/', true];
        yield ['/my-account', true];
        yield ['/admin', true];
    }

    /**
     * @dataProvider userPrivateUrlsProvider
     */
    public function testPrivateUrlsAsUser(string $url, bool $authorized): void
    {
        $client = static::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        if ($authorized) {
            $this->assertResponseIsSuccessful();
        } else {
            $this->assertResponseStatusCodeSame(403);
        }
    }

    public function userPrivateUrlsProvider(): \Iterator
    {
        yield ['/', true];
        yield ['/licensees', true];
        yield ['/events', true];
        yield ['/my-account', true];
        yield ['/my-profile', true];
        yield ['/admin', false];
    }

    public function testEvent(): void
    {
        $client = static::createLoggedInAsUserClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events');

        $eventLink = $crawler->filter('li.event-list-event > a')->link();
        $client->click($eventLink);

        $this->assertResponseIsSuccessful();
    }

    public function testDownloadToCalendar(): void
    {
        $client = static::createLoggedInAsUserClient();

        $eventRepository = self::getContainer()->get(EventRepository::class);
        $events = $eventRepository->findAll();
        $event = reset($events);
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/'.$event->getSlug());

        $calendarLink = $crawler->selectLink('Ajouter à mon calendrier')->link();
        $client->click($calendarLink);

        $this->assertResponseIsSuccessful();
    }
}
