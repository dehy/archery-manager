<?php

namespace App\Tests;

use App\DBAL\Types\UserRoleType;
use App\Entity\Event;
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
     * @dataProvider privateUrlsProvider
     */
    public function testPrivateUrlsAsAdmin(string $url, string $requiredRole): void
    {
        $client = static::createLoggedInAsAdminClient();
        $client->request('GET', $url);
        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider privateUrlsProvider
     */
    public function testPrivateUrlsAsUser(string $url, string $requiredRole): void
    {
        $client = static::createLoggedInAsUserClient();
        $client->request('GET', $url);
        if (UserRoleType::USER === $requiredRole) {
            self::assertResponseIsSuccessful();
        } else {
            self::assertResponseStatusCodeSame(403);
        }
    }

    public function privateUrlsProvider(): array
    {
        return [
            ['/', UserRoleType::USER],
            ['/licensees', UserRoleType::USER],
            ['/events', UserRoleType::USER],
            ['/my-account', UserRoleType::USER],
            ['/my-profile', UserRoleType::USER],
            ['/admin', UserRoleType::ADMIN],
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

        $event = $this->findEvent(1);
        $crawler = $client->request('GET', '/events/'.$event->getSlug());

        $calendarLink = $crawler->selectLink('Ajouter à mon calendrier')->link();
        $client->click($calendarLink);

        self::assertResponseIsSuccessful();
    }

    private function findEvent(int $id): ?Event
    {
        $eventRepository = self::getContainer()->get(EventRepository::class);

        return $eventRepository->find($id);
    }
}
