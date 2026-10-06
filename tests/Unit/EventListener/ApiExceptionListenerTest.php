<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\ApiExceptionListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiExceptionListenerTest extends TestCase
{
    /**
     * @return iterable<string, array{\Throwable, int, string}>
     */
    public static function httpExceptions(): iterable
    {
        yield 'bad request' => [new BadRequestHttpException('Invalid X-Season header.'), 400, 'bad_request'];
        yield 'unauthorized' => [new UnauthorizedHttpException('Bearer'), 401, 'unauthorized'];
        yield 'forbidden' => [new AccessDeniedHttpException('Unknown licensee in X-Licensee header.'), 403, 'forbidden'];
        yield 'not found' => [new NotFoundHttpException('No route found for "GET https://host/api/v1/secret"'), 404, 'not_found'];
        yield 'method not allowed' => [new MethodNotAllowedHttpException(['POST']), 405, 'method_not_allowed'];
        yield 'conflict' => [new ConflictHttpException(), 409, 'conflict'];
        yield 'unprocessable' => [new UnprocessableEntityHttpException(), 422, 'unprocessable_entity'];
        yield 'too many requests' => [new TooManyRequestsHttpException(30), 429, 'too_many_requests'];
        yield 'payload too large' => [new HttpException(413), 413, 'payload_too_large'];
        yield 'service unavailable' => [new HttpException(503), 503, 'service_unavailable'];
        yield 'unmapped status' => [new HttpException(418), 418, 'http_error'];
    }

    #[DataProvider('httpExceptions')]
    public function testHttpExceptionsBecomeJsonErrors(\Throwable $exception, int $status, string $code): void
    {
        $event = $this->event('/api/v1/me', $exception);

        new ApiExceptionListener(false)($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($status, $response->getStatusCode());
        $this->assertStringStartsWith('application/json', (string) $response->headers->get('content-type'));
        $body = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame($code, $body['error']);
        $this->assertSame(['error', 'message'], array_keys($body));
    }

    public function testTheMessageOfARoutingErrorIsNotLeaked(): void
    {
        $event = $this->event('/api/v1/secret', new NotFoundHttpException('No route found for "GET https://host/api/v1/secret"'));

        new ApiExceptionListener(false)($event);

        $this->assertStringNotContainsString('secret', (string) $event->getResponse()?->getContent());
    }

    public function testTheHeadersOfTheExceptionAreKept(): void
    {
        $event = $this->event('/api/v1/auth/login', new MethodNotAllowedHttpException(['POST']));

        new ApiExceptionListener(false)($event);

        $this->assertSame('POST', $event->getResponse()?->headers->get('Allow'));
    }

    public function testAnUnexpectedFailureIsAGenericJsonErrorInProduction(): void
    {
        $event = $this->event('/api/v1/me', new \RuntimeException('SQLSTATE[22001] secret details'));

        new ApiExceptionListener(false)($event);

        $this->assertSame(500, $event->getResponse()?->getStatusCode());
        $this->assertStringNotContainsString('SQLSTATE', (string) $event->getResponse()?->getContent());
        $this->assertSame('server_error', json_decode((string) $event->getResponse()?->getContent(), true)['error']);
    }

    public function testAnUnexpectedFailureKeepsTheDebugPageInDebugMode(): void
    {
        $event = $this->event('/api/v1/me', new \RuntimeException('boom'));

        new ApiExceptionListener(true)($event);

        $this->assertNotInstanceOf(Response::class, $event->getResponse());
    }

    #[DataProvider('webPaths')]
    public function testItNeverTouchesWebRequests(string $path): void
    {
        $event = $this->event($path, new NotFoundHttpException());

        new ApiExceptionListener(false)($event);

        $this->assertNotInstanceOf(Response::class, $event->getResponse());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function webPaths(): iterable
    {
        yield 'a web page' => ['/events'];
        yield 'the consent endpoint' => ['/api/consent'];
        yield 'api platform docs' => ['/api/docs'];
        yield 'a path sharing the prefix' => ['/api/v1foo'];
    }

    private function event(string $path, \Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent($this->createStub(HttpKernelInterface::class), Request::create($path), HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
