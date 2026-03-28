<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\application\LoggedInTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResponseHeadersSubscriberTest extends LoggedInTestCase
{
    private const string LOGIN_URL = '/login';

    private const string HEADER_CSP = 'Content-Security-Policy';

    private const string HEADER_X_CONTENT_TYPE_OPTIONS = 'X-Content-Type-Options';

    private const string HEADER_X_FRAME_OPTIONS = 'X-Frame-Options';

    private const string HEADER_HSTS = 'Strict-Transport-Security';

    public function testHeadersArePresentOnPublicRoute(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, self::LOGIN_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $client->getResponse();

        $this->assertTrue($response->headers->has(self::HEADER_CSP));
        $this->assertSame('nosniff', $response->headers->get(self::HEADER_X_CONTENT_TYPE_OPTIONS));
        $this->assertSame('DENY', $response->headers->get(self::HEADER_X_FRAME_OPTIONS));

        $csp = $response->headers->get(self::HEADER_CSP, '');
        $this->assertStringContainsString("default-src 'self'", (string) $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", (string) $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", (string) $csp);
    }

    public function testHeadersArePresentOnAuthenticatedRoute(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_GET, '/');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $client->getResponse();

        $this->assertTrue($response->headers->has(self::HEADER_CSP));
        $this->assertSame('nosniff', $response->headers->get(self::HEADER_X_CONTENT_TYPE_OPTIONS));
        $this->assertSame('DENY', $response->headers->get(self::HEADER_X_FRAME_OPTIONS));

        $csp = $response->headers->get(self::HEADER_CSP, '');
        $this->assertStringContainsString("connect-src 'self' https: wss:", (string) $csp);
        $this->assertStringContainsString("form-action 'self'", (string) $csp);
        $this->assertStringContainsString("base-uri 'self'", (string) $csp);
    }

    public function testHstsHeaderIsPresentOnSecureRequests(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, self::LOGIN_URL, server: ['HTTPS' => 'on']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $client->getResponse();

        $this->assertSame('max-age=31536000; includeSubDomains', $response->headers->get(self::HEADER_HSTS));
    }

    public function testHstsHeaderIsAbsentOnNonSecureRequests(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, self::LOGIN_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $client->getResponse();

        $this->assertFalse($response->headers->has(self::HEADER_HSTS));
    }
}
