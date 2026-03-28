<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseHeadersSubscriber implements EventSubscriberInterface
{
    private const string HEADER_CSP = 'Content-Security-Policy';

    private const string HEADER_X_CONTENT_TYPE_OPTIONS = 'X-Content-Type-Options';

    private const string HEADER_X_FRAME_OPTIONS = 'X-Frame-Options';

    private const string HEADER_REFERRER_POLICY = 'Referrer-Policy';

    private const string HEADER_HSTS = 'Strict-Transport-Security';

    private const string HSTS_VALUE = 'max-age=31536000; includeSubDomains';

    private const string REFERRER_POLICY_VALUE = 'strict-origin-when-cross-origin';

    private const string X_CONTENT_TYPE_OPTIONS_VALUE = 'nosniff';

    private const string X_FRAME_OPTIONS_VALUE = 'DENY';

    /**
     * @param array<string, string> $cspDirectives
     */
    public function __construct(
        private readonly array $cspDirectives,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $response->headers->set(self::HEADER_CSP, $this->buildCspHeaderValue());
        $response->headers->set(self::HEADER_X_CONTENT_TYPE_OPTIONS, self::X_CONTENT_TYPE_OPTIONS_VALUE);
        $response->headers->set(self::HEADER_X_FRAME_OPTIONS, self::X_FRAME_OPTIONS_VALUE);
        $response->headers->set(self::HEADER_REFERRER_POLICY, self::REFERRER_POLICY_VALUE);

        if ($request->isSecure()) {
            $response->headers->set(self::HEADER_HSTS, self::HSTS_VALUE);
        }
    }

    private function buildCspHeaderValue(): string
    {
        $parts = [];
        foreach ($this->cspDirectives as $directive => $value) {
            $normalizedValue = trim((string) preg_replace('/\s+/', ' ', trim($value)));
            if ('' === $normalizedValue) {
                continue;
            }

            $parts[] = \sprintf('%s %s', $directive, $normalizedValue);
        }

        return implode('; ', $parts);
    }
}
