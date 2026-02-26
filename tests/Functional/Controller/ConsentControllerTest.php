<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Repository\ConsentLogRepository;
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
            'services' => [],
            'action' => 'accepted',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testUnauthenticatedConsentPostDeclined(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'declined',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testConsentLogUserIsNullForUnauthenticated(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'accepted',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var ConsentLogRepository $repo */
        $repo = self::getContainer()->get(ConsentLogRepository::class);
        $logs = $repo->findBy([], ['id' => 'DESC'], 1);

        $this->assertCount(1, $logs);
        $this->assertNotInstanceOf(\App\Entity\User::class, $logs[0]->getUser());
    }

    public function testInvalidActionReturns400(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'invalid_action',
            'policyVersion' => '2026-02-24',
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

    // ── policyVersion validation ───────────────────────────────────────

    public function testEmptyPolicyVersionReturns400(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'accepted',
            'policyVersion' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testMissingPolicyVersionReturns400(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'accepted',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testTooLongPolicyVersionReturns400(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'accepted',
            'policyVersion' => str_repeat('a', 33),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testMaxLengthPolicyVersionReturns201(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'accepted',
            'policyVersion' => str_repeat('a', 32),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    // ── services validation ────────────────────────────────────────────

    public function testTooManyServicesReturns400(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => array_fill(0, 21, str_repeat('x', 10)),
            'action' => 'accepted',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testServiceStringTooLongReturns400(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [str_repeat('x', 65)],
            'action' => 'accepted',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUnknownServiceReturns400(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => ['unknown-tracker'],
            'action' => 'accepted',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testKnownServiceReturns201(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => ['matomo'],
            'action' => 'accepted',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testEmptyServicesReturns201(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => [],
            'action' => 'accepted',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testPartialActionWithSomeServicesReturns201AndStoresLog(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::URL_CONSENT, [
            'services' => ['matomo'],
            'action' => 'partial',
            'policyVersion' => '2026-02-24',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var ConsentLogRepository $repo */
        $repo = self::getContainer()->get(ConsentLogRepository::class);
        $logs = $repo->findBy([], ['id' => 'DESC'], 1);

        $this->assertCount(1, $logs);
        $this->assertSame('partial', $logs[0]->getAction());
        $this->assertSame(['matomo'], $logs[0]->getServices());
        $this->assertSame('2026-02-24', $logs[0]->getPolicyVersion());
    }

    // ── Authenticated ──────────────────────────────────────────────────
}
