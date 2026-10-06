<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every error under /api/v1 is JSON with a stable `error` code and an English `message`,
 * whatever the client sends in its Accept header (none of these requests set one).
 */
final class ApiErrorFormatTest extends WebTestCase
{
    private const string LOGIN_URL = '/api/v1/auth/login';

    private const string ME_URL = '/api/v1/me';

    private const string CREDENTIALS = '{"email":"clubadmin@ladg.com","password":"user"}';

    public function testLoginWithoutAJsonContentTypeIsUnsupportedMediaType(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_POST, self::LOGIN_URL, content: self::CREDENTIALS);
        $this->assertError($client, Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'unsupported_media_type');

        $client->request(Request::METHOD_POST, self::LOGIN_URL, server: ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], content: 'email=a&password=b');
        $this->assertError($client, Response::HTTP_UNSUPPORTED_MEDIA_TYPE, 'unsupported_media_type');
    }

    #[DataProvider('invalidLoginBodies')]
    public function testLoginWithAMalformedJsonBodyIsABadRequest(string $body): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_POST, self::LOGIN_URL, server: ['CONTENT_TYPE' => 'application/json'], content: $body);

        $this->assertError($client, Response::HTTP_BAD_REQUEST, 'bad_request');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidLoginBodies(): iterable
    {
        yield 'not json' => ['{'];
        yield 'missing email' => ['{"password":"user"}'];
        yield 'missing password' => ['{"email":"clubadmin@ladg.com"}'];
        yield 'non string email' => ['{"email":123,"password":"user"}'];
    }

    public function testLoginIsNotAffectedByAStaleBearerHeader(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_POST, self::LOGIN_URL, server: ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer stale'], content: self::CREDENTIALS);

        $this->assertResponseIsSuccessful();
    }

    public function testHealthIsNotAffectedByAStaleBearerHeader(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/api/v1/health', server: ['HTTP_AUTHORIZATION' => 'Bearer stale']);

        $this->assertResponseIsSuccessful();
    }

    public function testMissingCredentialsAreAnsweredWithAChallenge(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, self::ME_URL);

        $this->assertError($client, Response::HTTP_UNAUTHORIZED, 'authentication_required');
        $this->assertResponseHeaderSame('www-authenticate', 'Bearer');
    }

    public function testAnInvalidBearerTokenIsAnsweredWithAChallenge(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, self::ME_URL, server: ['HTTP_AUTHORIZATION' => 'Bearer nope']);

        $this->assertError($client, Response::HTTP_UNAUTHORIZED, 'invalid_token');
        $this->assertStringContainsString('invalid_token', (string) $client->getResponse()->headers->get('www-authenticate'));
    }

    public function testBadCredentialsAreAnsweredWithAGenericError(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_POST, self::LOGIN_URL, server: ['CONTENT_TYPE' => 'application/json'], content: '{"email":"clubadmin@ladg.com","password":"nope"}');

        $this->assertError($client, Response::HTTP_UNAUTHORIZED, 'invalid_credentials');
    }

    public function testUnknownRouteDoesNotLeakTheRequestedUrl(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/api/v1/does-not-exist');

        $this->assertError($client, Response::HTTP_NOT_FOUND, 'not_found');
        $this->assertStringNotContainsString('does-not-exist', (string) $client->getResponse()->getContent());
    }

    public function testWrongMethodIsMethodNotAllowed(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, self::LOGIN_URL);

        $this->assertError($client, Response::HTTP_METHOD_NOT_ALLOWED, 'method_not_allowed');
        $this->assertResponseHeaderSame('allow', 'POST');
    }

    public function testInvalidSeasonHeaderIsABadRequest(): void
    {
        $client = self::createClient();
        $token = $this->accessToken($client);

        $client->request(Request::METHOD_GET, self::ME_URL, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'HTTP_X_SEASON' => 'abc']);

        $this->assertError($client, Response::HTTP_BAD_REQUEST, 'bad_request');
    }

    public function testForeignLicenseeHeaderIsForbidden(): void
    {
        $client = self::createClient();
        $token = $this->accessToken($client);
        $coach = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'coach@ladg.com']);
        $foreignCode = $coach?->getLicensees()->first()->getFftaMemberCode();
        $this->assertNotNull($foreignCode);

        $client->request(Request::METHOD_GET, self::ME_URL, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'HTTP_X_LICENSEE' => $foreignCode]);

        $this->assertError($client, Response::HTTP_FORBIDDEN, 'forbidden');
    }

    /**
     * A percent-encoded path reaches the API firewall and router as `/api/v1/...`, so the
     * header-based context must apply to it too instead of falling back to the web session.
     */
    public function testAPercentEncodedApiPathStillUsesTheRequestHeaders(): void
    {
        $client = self::createClient();
        $token = $this->accessToken($client);

        $client->request(Request::METHOD_GET, '/%61pi/v1/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'HTTP_X_SEASON' => '2025']);

        $this->assertResponseIsSuccessful();
        $this->assertSame(2025, json_decode((string) $client->getResponse()->getContent(), true)['context']['season']);
    }

    private function accessToken(KernelBrowser $client): string
    {
        $client->request(Request::METHOD_POST, self::LOGIN_URL, server: ['CONTENT_TYPE' => 'application/json'], content: self::CREDENTIALS);
        $this->assertResponseIsSuccessful();

        return json_decode((string) $client->getResponse()->getContent(), true)['access_token'];
    }

    private function assertError(KernelBrowser $client, int $status, string $code): void
    {
        $response = $client->getResponse();

        $this->assertSame($status, $response->getStatusCode(), (string) $response->getContent());
        $this->assertStringStartsWith('application/json', (string) $response->headers->get('content-type'));
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame($code, $body['error']);
        $this->assertNotSame('', $body['message']);
        $this->assertSame(['error', 'message'], array_keys($body));
    }
}
