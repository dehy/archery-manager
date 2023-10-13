<?php

namespace App\Tests\application;

use App\Repository\EventRepository;

/**
 * @internal
 */
class SmokeTest extends LoggedInTestCase
{
    /**
     * @dataProvider publicUrlsProvider
     */
    public function testPublicUrls(string $url): void
    {
        $client = self::createClient();
        $client->request('GET', $url);
        self::assertResponseIsSuccessful();
    }

    public function publicUrlsProvider(): array
    {
        return [
            ['/login'],
            ['/pre-inscription'],
            ['/pre-inscription/merci'],
            ['/pre-inscription-renouvellement'],
            ['/pre-inscription-renouvellement/merci'],
        ];
    }

    /**
     * @dataProvider adminPrivateUrlsProvider
     */
    public function testPrivateUrlsAsAdmin(string $url): void
    {
        $client = static::createLoggedInAsAdminClient();
        $client->request('GET', $url);
        self::assertResponseIsSuccessful();
    }

    public function adminPrivateUrlsProvider(): array
    {
        return [
            ['/', true],
            ['/my-account', true],
            ['/admin', true],
        ];
    }

    /**
     * @dataProvider userPrivateUrlsProvider
     */
    public function testPrivateUrlsAsUser(string $url, bool $authorized): void
    {
        $client = static::createLoggedInAsUserClient();
        $client->request('GET', $url);
        if ($authorized) {
            self::assertResponseIsSuccessful();
        } else {
            self::assertResponseStatusCodeSame(403);
        }
    }

    public function userPrivateUrlsProvider(): array
    {
        return [
            ['/', true],
            ['/licensees', true],
            ['/events', true],
            ['/my-account', true],
            ['/my-profile', true],
            ['/admin', false],
        ];
    }

    public function testEvent(): void
    {
        $client = static::createLoggedInAsUserClient();
        $crawler = $client->request('GET', '/events');

        $eventLink = $crawler->filter('li.event-list-event > a')->link();
        $client->click($eventLink);

        self::assertResponseIsSuccessful();
    }

    public function testDownloadToCalendar(): void
    {
        $client = static::createLoggedInAsUserClient();

        $eventRepository = self::getContainer()->get(EventRepository::class);
        $events = $eventRepository->findAll();
        $event = reset($events);
        $crawler = $client->request('GET', '/events/'.$event->getSlug());

        $calendarLink = $crawler->selectLink('Ajouter à mon calendrier')->link();
        $client->click($calendarLink);

        self::assertResponseIsSuccessful();
    }
}
