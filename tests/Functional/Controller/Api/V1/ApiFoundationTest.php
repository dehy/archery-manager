<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiFoundationTest extends WebTestCase
{
    public function testHealthIsPublicAndReturnsJson(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/v1/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonStringEqualsJsonString(
            '{"status":"ok","version":"v1"}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testHealthDoesNotStartASession(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/v1/health');

        $this->assertNotInstanceOf(Cookie::class, $client->getResponse()->headers->getCookies()[0] ?? null);
    }

    public function testUnknownApiV1RouteReturnsNotFound(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/v1/does-not-exist');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * The former bare #[ApiResource] entities exposed full CRUD to any user.
     * They must no longer be reachable, even for an authenticated session.
     */
    #[DataProvider('removedEntityEndpoints')]
    public function testFormerEntityEndpointsAreGone(string $path): void
    {
        $client = self::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy([]);
        $this->assertNotNull($user, 'Fixtures must provide at least one user.');
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, $path);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function removedEntityEndpoints(): iterable
    {
        yield 'licensees' => ['/api/licensees'];
        yield 'licenses' => ['/api/licenses'];
        yield 'results' => ['/api/results'];
        yield 'event participations' => ['/api/event_participations'];
        yield 'attachments' => ['/api/attachments'];
    }
}
