<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\Api\ApiErrorResponse;
use App\Security\Api\ApiRequest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Answers every error raised under /api/v1 with the API's JSON error shape instead of an HTML page.
 *
 * It runs after the exception logger (priority 0) and the security firewall's own handling (priority 1),
 * and before Symfony's default HTML error renderer (priority -128).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
final readonly class ApiExceptionListener
{
    private const array CODES = [
        Response::HTTP_BAD_REQUEST => 'bad_request',
        Response::HTTP_FORBIDDEN => 'forbidden',
        Response::HTTP_NOT_FOUND => 'not_found',
        Response::HTTP_METHOD_NOT_ALLOWED => 'method_not_allowed',
        Response::HTTP_NOT_ACCEPTABLE => 'not_acceptable',
        Response::HTTP_UNSUPPORTED_MEDIA_TYPE => 'unsupported_media_type',
        Response::HTTP_TOO_MANY_REQUESTS => 'too_many_requests',
    ];

    /**
     * Statuses whose exception message is written by us and safe to show; the others
     * (404 carries the requested URL, for instance) get a generic text.
     */
    private const array SAFE_MESSAGE_STATUSES = [Response::HTTP_BAD_REQUEST, Response::HTTP_FORBIDDEN, Response::HTTP_UNSUPPORTED_MEDIA_TYPE];

    public function __construct(#[Autowire('%kernel.debug%')] private bool $debug)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!ApiRequest::is($event->getRequest())) {
            return;
        }

        $exception = $event->getThrowable();
        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = \in_array($status, self::SAFE_MESSAGE_STATUSES, true) && '' !== $exception->getMessage()
                ? $exception->getMessage()
                : (Response::$statusTexts[$status] ?? 'Error');

            $response = ApiErrorResponse::create(self::CODES[$status] ?? 'http_error', $message, $status);
            $response->headers->add($exception->getHeaders());
            $event->setResponse($response);

            return;
        }

        // Unexpected failure: keep the debug page in dev, never leak details in production.
        if (!$this->debug) {
            $event->setResponse(ApiErrorResponse::create('server_error', 'Internal server error.', Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }
}
