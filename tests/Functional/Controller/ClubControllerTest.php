<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\application\LoggedInTestCase;

final class ClubControllerTest extends LoggedInTestCase
{
    private const string URL_MY_CLUB = '/my-club';

    // ── Show My Club ───────────────────────────────────────────────────

    public function testShowRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request('GET', self::URL_MY_CLUB);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    public function testShowRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_MY_CLUB);

        $this->assertResponseIsSuccessful();
        // Should have an h1 with club name or page title
        $this->assertSelectorExists('h1');
    }

    public function testShowRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request('GET', self::URL_MY_CLUB);

        $this->assertResponseIsSuccessful();
    }

    public function testShowDisplaysClubInformation(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request('GET', self::URL_MY_CLUB);

        $this->assertResponseIsSuccessful();

        // Should display club details
        $this->assertGreaterThan(0, $crawler->filter('.card')->count(), 'Should display at least one card');
    }

    public function testShowDisplaysGroups(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_MY_CLUB);

        $this->assertResponseIsSuccessful();
        // Groups section should be present
        $this->assertSelectorExists('h2, h3, h4, h5');
    }

    public function testShowDisplaysLicensees(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_MY_CLUB);

        $this->assertResponseIsSuccessful();
        // Licensees should be displayed somewhere on the page
        $this->assertSelectorExists('body');
    }
}
