<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\SecurityLog;
use App\EventListener\AuthenticationFailureListener;
use App\Service\SecurityNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

final class AuthenticationFailureListenerTest extends TestCase
{
    public function testAThrottledApiLoginIsLoggedAsRateLimitedWithoutStartingASession(): void
    {
        $logged = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($this->createStub(EntityRepository::class));
        $entityManager->expects($this->once())->method('persist')->willReturnCallback(static function (object $entity) use (&$logged): void {
            $logged[] = $entity;
        });
        $entityManager->expects($this->once())->method('flush');

        // No session is available on the stateless firewall: touching it would throw.
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->never())->method('getSession');

        $listener = new AuthenticationFailureListener($entityManager, new NullLogger(), $requestStack, $this->createStub(SecurityNotificationService::class));
        $request = Request::create('/api/v1/auth/login', Request::METHOD_POST, server: ['CONTENT_TYPE' => 'application/json'], content: '{"email":"someone@example.com","password":"x"}');

        $listener->onLoginFailure(new LoginFailureEvent(
            new TooManyLoginAttemptsAuthenticationException(15),
            $this->createStub(AuthenticatorInterface::class),
            $request,
            null,
            'api',
        ));

        $this->assertCount(1, $logged);
        $this->assertInstanceOf(SecurityLog::class, $logged[0]);
        $this->assertSame(SecurityLog::EVENT_RATE_LIMITED, $logged[0]->getEventType());
        $this->assertSame('someone@example.com', $logged[0]->getEmail());
    }
}
