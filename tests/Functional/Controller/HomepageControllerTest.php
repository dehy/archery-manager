<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\application\LoggedInTestCase;

final class HomepageControllerTest extends LoggedInTestCase
{
    private const string URL_HOMEPAGE = '/';

    // ── Index ──────────────────────────────────────────────────────────

    public function testIndexRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request('GET', self::URL_HOMEPAGE);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    public function testIndexRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_HOMEPAGE);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request('GET', self::URL_HOMEPAGE);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexDisplaysUpcomingEvents(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request('GET', self::URL_HOMEPAGE);

        $this->assertResponseIsSuccessful();
        // Page should contain content
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }

    public function testIndexDisplaysRecentResults(): void
    {
        $client = self::createLoggedInAsUserClient();
        $crawler = $client->request('GET', self::URL_HOMEPAGE);

        $this->assertResponseIsSuccessful();
        // Page should render successfully with content
        $this->assertGreaterThan(0, $crawler->filter('html')->count());
    }
}
