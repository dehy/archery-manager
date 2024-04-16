<?php

namespace App\Tests\application;

use App\Factory\EventFactory;
use App\Factory\LicenseFactory;
use App\Factory\UserFactory;
use App\Repository\EventRepository;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @internal
 */
class SmokeTest extends LoggedInTestCase
{
    use Factories;
    use ResetDatabase;

    /**
     * @dataProvider publicUrlsProvider
     */
    public function testPublicUrls(string $url): void
    {
        // 1. Arrange

        // 2. Act
        $client = self::createClient();
        $client->request('GET', $url);

        // 3. Assert
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
        // 1. Arrange
        $client = $this->createClient();
        $admin = UserFactory::new()->admin()->create();
        $client->loginUser($admin->object());

        // 2. Act
        $client->request('GET', $url);

        // 3. Assert
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
        // 1. Arrange
        $client = $this->createClient();
        $license = LicenseFactory::new()->create();
        $client->loginUser($license->getLicensee()->getUser());

        // 2. Act
        $client->request('GET', $url);

        // 3. Assert
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
        // 1. Arrange
        $client = $this->createClient();
        $license = LicenseFactory::createOne();
        $client->loginUser($license->getLicensee()->getUser());

        EventFactory::createOne([
            'club' => $license->getLicensee()->getClubs()->first(),
        ]);

        // 2. Act
        $crawler = $client->request('GET', '/events?y=2023&m=9');

        $eventLink = $crawler->filter('li.event-list-event > a')->link();
        $client->click($eventLink);

        // 3. Assert
        self::assertResponseIsSuccessful();
    }

    public function testDownloadToCalendar(): void
    {
        // 1. Arrange
        $client = $this->createClient();
        $license = LicenseFactory::createOne();
        $user = $license->getLicensee()->getUser();
        $client->loginUser($user);

        $event = EventFactory::createOne();

        // 2. Act
        $crawler = $client->request('GET', '/events/'.$event->getSlug());

        $calendarLink = $crawler->selectLink('Ajouter à mon calendrier')->link();
        $client->click($calendarLink);

        // 3. Assert
        self::assertResponseIsSuccessful();
    }
}
