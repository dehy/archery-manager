<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Repository\ConsentLogRepository;
use App\Tests\application\LoggedInTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConsentControllerTest extends WebTestCase
{
    private const string URL_CONSENT = '/api/consent';

    // ── Unauthenticated ────────────────────────────────────────────────

    public function testUnauthenticatedConsentPostAccepted(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => ['matomo'],
            'action' => 'accepted',
            'policyVersion' => '2026-02-23',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testUnauthenticatedConsentPostDeclined(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'declined',
            'policyVersion' => '2026-02-23',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testConsentLogUserIsNullForUnauthenticated(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => ['matomo'],
            'action' => 'accepted',
            'policyVersion' => '2026-02-23',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var ConsentLogRepository $repo */
        $repo = static::getContainer()->get(ConsentLogRepository::class);
        $logs = $repo->findBy([], ['id' => 'DESC'], 1);

        $this->assertCount(1, $logs);
        $this->assertNull($logs[0]->getUser());
    }

    public function testInvalidActionReturns400(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => ['matomo'],
            'action' => 'invalid_action',
            'policyVersion' => '2026-02-23',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testInvalidJsonBodyReturns400(): void
    {
        $client = self::createClient();
        $client->request(
            Request::METHOD_POST,
            self::URL_CONSENT,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not-valid-json{',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ── Authenticated ──────────────────────────────────────────────────
}

final class ConsentControllerAuthenticatedTest extends LoggedInTestCase
{
    private const string URL_CONSENT = '/api/consent';

    public function testAuthenticatedConsentPostReturnsCreated(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => ['matomo'],
            'action' => 'accepted',
            'policyVersion' => '2026-02-23',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testConsentLogUserIsSetForAuthenticated(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => ['matomo'],
            'action' => 'accepted',
            'policyVersion' => '2026-02-23',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var ConsentLogRepository $repo */
        $repo = static::getContainer()->get(ConsentLogRepository::class);
        $logs = $repo->findBy([], ['id' => 'DESC'], 1);

        $this->assertCount(1, $logs);
        $this->assertNotNull($logs[0]->getUser());
    }
}
