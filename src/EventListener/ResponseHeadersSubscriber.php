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

    private const string HEADER_REPORTING_ENDPOINTS = 'Reporting-Endpoints';

    private const string HEADER_REPORT_TO = 'Report-To';

    private const string HEADER_NEL = 'NEL';

    private const string HEADER_PERMISSIONS_POLICY = 'Permissions-Policy';

    private const string HEADER_COEP_REPORT_ONLY = 'Cross-Origin-Embedder-Policy-Report-Only';

    private const string HEADER_COOP_REPORT_ONLY = 'Cross-Origin-Opener-Policy-Report-Only';

    private const string COEP_REPORT_ONLY_VALUE = 'require-corp; report-to="' . self::REPORTING_GROUP . '"';

    private const string COOP_REPORT_ONLY_VALUE = 'same-origin; report-to="' . self::REPORTING_GROUP . '"';

    private const array PERMISSIONS_POLICY_DIRECTIVES = [
        'accelerometer',
        'ambient-light-sensor',
        'autoplay',
        'battery',
        'camera',
        'clipboard-read',
        'display-capture',
        'document-domain',
        'encrypted-media',
        'execution-while-not-rendered',
        'execution-while-out-of-viewport',
        'fullscreen',
        'gamepad',
        'geolocation',
        'gyroscope',
        'keyboard-map',
        'magnetometer',
        'microphone',
        'midi',
        'payment',
        'picture-in-picture',
        'publickey-credentials-get',
        'screen-wake-lock',
        'speaker-selection',
        'sync-xhr',
        'usb',
        'web-share',
        'xr-spatial-tracking',
    ];

    private const string HSTS_VALUE = 'max-age=31536000; includeSubDomains';

    private const string REFERRER_POLICY_VALUE = 'strict-origin-when-cross-origin';

    private const string X_CONTENT_TYPE_OPTIONS_VALUE = 'nosniff';

    private const string X_FRAME_OPTIONS_VALUE = 'DENY';

    private const string REPORTING_GROUP = 'default';

    /**
     * @param array<string, string> $cspDirectives
     */
    public function __construct(
        private readonly array $cspDirectives,
        private readonly ?string $matomoUrl = null,
        private readonly ?string $reportingUrl = null,
        private readonly ?string $cspReportUrl = null,
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
        $response->headers->set(self::HEADER_PERMISSIONS_POLICY, $this->buildPermissionsPolicyValue());
        $response->headers->set(self::HEADER_COEP_REPORT_ONLY, self::COEP_REPORT_ONLY_VALUE);
        $response->headers->set(self::HEADER_COOP_REPORT_ONLY, self::COOP_REPORT_ONLY_VALUE);

        if ($request->isSecure()) {
            $response->headers->set(self::HEADER_HSTS, self::HSTS_VALUE);
        }

        if (null !== $this->reportingUrl && '' !== $this->reportingUrl) {
            $response->headers->set(
                self::HEADER_REPORTING_ENDPOINTS,
                self::REPORTING_GROUP . '="' . $this->reportingUrl . '"',
            );
            $response->headers->set(
                self::HEADER_REPORT_TO,
                (string) json_encode([
                    'group' => self::REPORTING_GROUP,
                    'max_age' => 10886400,
                    'endpoints' => [['url' => $this->reportingUrl]],
                    'include_subdomains' => true,
                ]),
            );
            $response->headers->set(
                self::HEADER_NEL,
                (string) json_encode([
                    'report_to' => self::REPORTING_GROUP,
                    'max_age' => 2592000,
                    'include_subdomains' => true,
                    'failure_fraction' => 1.0,
                ]),
            );
        }
    }

    private function buildPermissionsPolicyValue(): string
    {
        return implode(', ', array_map(
            static fn (string $directive): string => $directive . '=();report-to=' . self::REPORTING_GROUP,
            self::PERMISSIONS_POLICY_DIRECTIVES,
        ));
    }

    private function buildCspHeaderValue(): string
    {
        $directives = $this->cspDirectives;

        if (null !== $this->matomoUrl && '' !== $this->matomoUrl) {
            $scheme = parse_url($this->matomoUrl, PHP_URL_SCHEME);
            $host = parse_url($this->matomoUrl, PHP_URL_HOST);
            if (is_string($scheme) && is_string($host)) {
                $origin = $scheme . '://' . $host;
                $directives['script-src'] = ($directives['script-src'] ?? "'self'") . ' ' . $origin;
            }
        }

        $parts = [];
        foreach ($directives as $directive => $value) {
            $normalizedValue = trim((string) preg_replace('/\s+/', ' ', trim($value)));
            if ('' === $normalizedValue) {
                continue;
            }

            $parts[] = \sprintf('%s %s', $directive, $normalizedValue);
        }

        if (null !== $this->cspReportUrl && '' !== $this->cspReportUrl) {
            $parts[] = 'report-uri ' . $this->cspReportUrl;
        }

        if (null !== $this->reportingUrl && '' !== $this->reportingUrl) {
            $parts[] = 'report-to ' . self::REPORTING_GROUP;
        }

        return implode('; ', $parts);
    }
}
