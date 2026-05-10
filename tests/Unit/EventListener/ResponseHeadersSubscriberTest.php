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
    ): ResponseHeadersSubscriber {
        return new ResponseHeadersSubscriber($cspDirectives, $matomoUrl);
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
}
