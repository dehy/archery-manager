<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    /**
     * @dataProvider publicUrlsProvider
     */
    public function testPublicUrls($url): void
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
}
