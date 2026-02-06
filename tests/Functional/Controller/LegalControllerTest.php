<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LegalControllerTest extends WebTestCase
{
    public function testCguRendersSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/cgu');

        $this->assertResponseIsSuccessful();
    }

    public function testPrivacyPolicyRendersSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/politique-de-confidentialite');

        $this->assertResponseIsSuccessful();
    }

    public function testCguContainsExpectedContent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/cgu');

        $this->assertResponseIsSuccessful();
        // The page should render some HTML content from the markdown
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }

    public function testPrivacyPolicyContainsExpectedContent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/politique-de-confidentialite');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }
}
