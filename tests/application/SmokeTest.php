<?php

declare(strict_types=1);

namespace App\Tests\application;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class SmokeTest extends LoggedInTestCase
{
    #[DataProvider('publicUrlsProvider')]
    public function testPublicUrls(string $url): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
    }

    public static function publicUrlsProvider(): \Iterator
    {
        yield ['/login'];
    }

    #[DataProvider('adminPrivateUrlsProvider')]
    public function testPrivateUrlsAsAdmin(string $url): void
    {
        $client = static::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();
    }

    public static function adminPrivateUrlsProvider(): \Iterator
    {
        yield ['/', true];
        yield ['/my-account', true];
        yield ['/admin', true];
    }

    #[DataProvider('userPrivateUrlsProvider')]
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

    public static function userPrivateUrlsProvider(): \Iterator
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
}
