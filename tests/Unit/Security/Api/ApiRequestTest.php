<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Api;

use App\Security\Api\ApiRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiRequestTest extends TestCase
{
    #[DataProvider('paths')]
    public function testItMatchesTheSamePathsAsTheApiFirewall(string $uri, bool $expected): void
    {
        $this->assertSame($expected, ApiRequest::is(Request::create($uri)));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function paths(): iterable
    {
        yield 'me' => ['/api/v1/me', true];
        yield 'with query string' => ['/api/v1/me?x=1', true];
        yield 'bare prefix' => ['/api/v1', true];
        yield 'percent-encoded letter' => ['/%61pi/v1/me', true];
        yield 'percent-encoded slash' => ['/api%2Fv1/me', true];
        yield 'web page' => ['/events', false];
        yield 'consent endpoint' => ['/api/consent', false];
        yield 'api platform docs' => ['/api/docs', false];
        yield 'other version' => ['/api/v2/me', false];
        yield 'case differs' => ['/API/v1/me', false];
    }

    public function testNoRequestIsNotAnApiRequest(): void
    {
        $this->assertFalse(ApiRequest::is(null));
    }
}
