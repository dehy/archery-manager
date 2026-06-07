<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\ResponseHeadersSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ResponseHeadersSubscriberTest extends TestCase
{
    /**
     * @param array<string, string> $cspDirectives
     */
    private function createSubscriber(
        array $cspDirectives = ["default-src" => "'self'", "script-src" => "'self'"],
        ?string $matomoUrl = null,
        ?string $reportingUrl = null,
        ?string $cspReportUrl = null,
        string $environment = 'prod',
    ): ResponseHeadersSubscriber {
        return new ResponseHeadersSubscriber($environment, $cspDirectives, $matomoUrl, $reportingUrl, $cspReportUrl);
    }

    private function createResponseEvent(bool $isMainRequest = true, bool $isSecure = false): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $server = $isSecure ? ['HTTPS' => 'on'] : [];
        $request = Request::create('/', \Symfony\Component\HttpFoundation\Request::METHOD_GET, [], [], [], $server);
        $response = new Response();

        return new ResponseEvent(
            $kernel,
            $request,
            $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
            $response,
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = ResponseHeadersSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertSame('onKernelResponse', $events[KernelEvents::RESPONSE]);
    }

    public function testSubRequestIsIgnored(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createResponseEvent(isMainRequest: false);

        $subscriber->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('Content-Security-Policy'));
    }

    public function testMatomoUrlIsAddedToScriptSrc(): void
    {
        $subscriber = $this->createSubscriber(
            cspDirectives: ["default-src" => "'self'", "script-src" => "'self'"],
            matomoUrl: 'https://matomo.example.com',
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $csp = $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' https://matomo.example.com", (string) $csp);
    }

    public function testMatomoUrlWithPathUsesOnlyOrigin(): void
    {
        $subscriber = $this->createSubscriber(
            cspDirectives: ["script-src" => "'self'"],
            matomoUrl: 'https://matomo.example.com/piwik.php',
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $csp = $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' https://matomo.example.com", (string) $csp);
        $this->assertStringNotContainsString('piwik.php', (string) $csp);
    }

    public function testMatomoUrlDefaultsScriptSrcToSelfWhenDirectiveAbsent(): void
    {
        $subscriber = $this->createSubscriber(
            cspDirectives: ["default-src" => "'self'"],
            matomoUrl: 'https://matomo.example.com',
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $csp = $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' https://matomo.example.com", (string) $csp);
    }

    public function testNullMatomoUrlDoesNotModifyScriptSrc(): void
    {
        $subscriber = $this->createSubscriber(
            cspDirectives: ["script-src" => "'self'"],
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $csp = $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertSame("script-src 'self'", $csp);
    }

    public function testEmptyMatomoUrlDoesNotModifyScriptSrc(): void
    {
        $subscriber = $this->createSubscriber(
            cspDirectives: ["script-src" => "'self'"],
            matomoUrl: '',
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $csp = $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertSame("script-src 'self'", $csp);
    }

    public function testEmptyDirectiveValueIsSkipped(): void
    {
        $subscriber = $this->createSubscriber(
            cspDirectives: ["script-src" => "'self'", "plugin-types" => ""],
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $csp = $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", (string) $csp);
        $this->assertStringNotContainsString('plugin-types', (string) $csp);
    }

    public function testHstsHeaderIsSetOnSecureRequest(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createResponseEvent(isMainRequest: true, isSecure: true);

        $subscriber->onKernelResponse($event);

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $event->getResponse()->headers->get('Strict-Transport-Security'),
        );
    }

    public function testHstsHeaderIsAbsentOnNonSecureRequest(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createResponseEvent(isMainRequest: true, isSecure: false);

        $subscriber->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('Strict-Transport-Security'));
    }

    public function testReportingUrlAddsReportingHeaders(): void
    {
        $subscriber = $this->createSubscriber(
            reportingUrl: 'https://reporting.example.com/reports',
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertSame(
            'default="https://reporting.example.com/reports"',
            $headers->get('Reporting-Endpoints'),
        );
        $this->assertStringContainsString(
            'reporting.example.com',
            (string) $headers->get('Report-To'),
        );
        $this->assertStringContainsString(
            '"report_to":"default"',
            (string) $headers->get('NEL'),
        );
    }

    public function testReportingUrlAddsCspReportDirectives(): void
    {
        $subscriber = $this->createSubscriber(
            cspDirectives: ["default-src" => "'self'"],
            reportingUrl: 'https://reporting.example.com/reports',
            cspReportUrl: 'https://csp.example.com/reports',
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $csp = (string) $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('report-uri https://csp.example.com/reports', $csp);
        $this->assertStringContainsString('report-to default', $csp);
    }

    public function testNullReportingUrlDoesNotAddReportingHeaders(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertFalse($headers->has('Reporting-Endpoints'));
        $this->assertFalse($headers->has('Report-To'));
        $this->assertFalse($headers->has('NEL'));
        $this->assertStringNotContainsString('report-uri', (string) $headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('report-to', (string) $headers->get('Content-Security-Policy'));
    }

    public function testEmptyReportingUrlDoesNotAddReportingHeaders(): void
    {
        $subscriber = $this->createSubscriber(reportingUrl: '');
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertFalse($headers->has('Reporting-Endpoints'));
        $this->assertFalse($headers->has('Report-To'));
        $this->assertFalse($headers->has('NEL'));
    }

    public function testCspReportUrlOnlyAddsReportUri(): void
    {
        $subscriber = $this->createSubscriber(
            cspDirectives: ["default-src" => "'self'"],
            cspReportUrl: 'https://csp.example.com/reports',
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $csp = (string) $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('report-uri https://csp.example.com/reports', $csp);
        $this->assertStringNotContainsString('report-to', $csp);
        $this->assertFalse($event->getResponse()->headers->has('Reporting-Endpoints'));
    }

    public function testReportingUrlOnlyAddsReportTo(): void
    {
        $subscriber = $this->createSubscriber(
            cspDirectives: ["default-src" => "'self'"],
            reportingUrl: 'https://reporting.example.com/reports',
        );
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $csp = (string) $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('report-to default', $csp);
        $this->assertStringNotContainsString('report-uri', $csp);
    }

    public function testPermissionsPolicyHeaderIsAlwaysSet(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $policy = (string) $event->getResponse()->headers->get('Permissions-Policy');
        $this->assertStringContainsString('geolocation=()', $policy);
        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('report-to=default', $policy);
        $this->assertStringNotContainsString('clipboard-read=()', $policy);
        $this->assertStringNotContainsString('clipboard-write=()', $policy);
        $this->assertStringNotContainsString('cross-origin-isolated=()', $policy);
        $this->assertStringNotContainsString('navigation-override=()', $policy);
    }

    public function testCoepAndCoopReportOnlyHeadersAreAlwaysSet(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $coep = (string) $event->getResponse()->headers->get('Cross-Origin-Embedder-Policy-Report-Only');
        $this->assertStringContainsString('require-corp', $coep);
        $this->assertStringContainsString('report-to="default"', $coep);

        $coop = (string) $event->getResponse()->headers->get('Cross-Origin-Opener-Policy-Report-Only');
        $this->assertStringContainsString('same-origin', $coop);
        $this->assertStringContainsString('report-to="default"', $coop);
    }

    public function testIntegrityPolicyReportOnlyHeaderIsAlwaysSet(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createResponseEvent();

        $subscriber->onKernelResponse($event);

        $policy = (string) $event->getResponse()->headers->get('Integrity-Policy-Report-Only');
        $this->assertStringContainsString('blocked-destinations=(script)', $policy);
        $this->assertStringContainsString('endpoints=(default)', $policy);
    }

    /**
     * @dataProvider nonProductionEnvironmentProvider
     */
    public function testNoSecurityHeadersAreSetOutsideProduction(string $environment): void
    {
        $subscriber = $this->createSubscriber(environment: $environment);
        $event = $this->createResponseEvent(isSecure: true);

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertFalse($headers->has('Content-Security-Policy'));
        $this->assertFalse($headers->has('X-Content-Type-Options'));
        $this->assertFalse($headers->has('X-Frame-Options'));
        $this->assertFalse($headers->has('Referrer-Policy'));
        $this->assertFalse($headers->has('Strict-Transport-Security'));
        $this->assertFalse($headers->has('Permissions-Policy'));
        $this->assertFalse($headers->has('Cross-Origin-Embedder-Policy-Report-Only'));
        $this->assertFalse($headers->has('Cross-Origin-Opener-Policy-Report-Only'));
        $this->assertFalse($headers->has('Integrity-Policy-Report-Only'));
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function nonProductionEnvironmentProvider(): \Iterator
    {
        yield 'dev' => ['dev'];
        yield 'test' => ['test'];
    }
}
