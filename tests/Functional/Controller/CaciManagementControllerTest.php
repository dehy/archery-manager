<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\application\LoggedInTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
final class CaciManagementControllerTest extends LoggedInTestCase
{
    private const string URL_CACI = '/licensees/manage/caci';

    public function testCaciPageRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, self::URL_CACI);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testCaciPageDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, self::URL_CACI);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCaciPageDeniedForCoach(): void
    {
        $client = self::createLoggedInAsCoachClient();
        $client->request(Request::METHOD_GET, self::URL_CACI);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCaciPageRendersForClubAdmin(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(Request::METHOD_GET, self::URL_CACI);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
        $this->assertSelectorTextContains('h2', 'CACI');
    }

    public function testCaciPageRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_GET, self::URL_CACI);

        // Admin has ROLE_CLUB_ADMIN so access is granted; may redirect if no active club in current season
        $this->assertNotSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }
}
