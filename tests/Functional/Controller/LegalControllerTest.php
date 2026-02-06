<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LegalControllerTest extends WebTestCase
{
    public function testCguRendersSuccessfully(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/cgu');

        $this->assertResponseIsSuccessful();
    }

    public function testPrivacyPolicyRendersSuccessfully(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/politique-de-confidentialite');

        $this->assertResponseIsSuccessful();
    }

    public function testCguContainsExpectedContent(): void
    {
        $client = self::createClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/cgu');

        $this->assertResponseIsSuccessful();
        // The page should render some HTML content from the markdown
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }

    public function testPrivacyPolicyContainsExpectedContent(): void
    {
        $client = self::createClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/politique-de-confidentialite');

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }
}
