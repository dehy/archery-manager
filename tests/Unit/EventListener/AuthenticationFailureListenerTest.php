<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\SecurityLog;
use App\Entity\User;
use App\EventListener\AuthenticationFailureListener;
use App\Service\SecurityNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AccessTokenAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

final class AuthenticationFailureListenerTest extends TestCase
{
    /** @var list<object> */
    private array $persisted = [];

    public function testAThrottledApiLoginIsOnlyLoggedAndNeverTouchesTheDatabaseOrTheSession(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('getRepository');
        $entityManager->expects($this->never())->method('persist');
        $entityManager->expects($this->never())->method('flush');
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->never())->method('getSession');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with('Login attempt throttled', ['email' => 'someone@example.com', 'ip' => '127.0.0.1']);

        $this->listener($entityManager, $logger, $requestStack)->onLoginFailure(new LoginFailureEvent(
            new TooManyLoginAttemptsAuthenticationException(15),
            $this->createStub(AuthenticatorInterface::class),
            $this->jsonLoginRequest('someone@example.com'),
            null,
            'api_login',
        ));
    }

    public function testARejectedBearerTokenIsNotALoginAttempt(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $this->listener($entityManager)->onLoginFailure(new LoginFailureEvent(
            new BadCredentialsException(),
            $this->createStub(AccessTokenAuthenticator::class),
            new Request(),
            null,
            'api',
        ));
    }

    public function testAnApiLoginFailureIsCountedWithoutStartingASession(): void
    {
        $user = new User()->setEmail('known@example.com');
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->never())->method('getSession');

        $this->listener($this->entityManagerFinding($user), requestStack: $requestStack)->onLoginFailure($this->failureOnFirewall('api_login', $this->jsonLoginRequest('known@example.com')));

        $this->assertSame(1, $user->getFailedLoginAttempts());
        $this->assertSame([SecurityLog::EVENT_FAILED_LOGIN], $this->persistedLogTypes());
    }

    public function testAWebLoginFailureIsCountedAndFeedsTheCaptchaCounter(): void
    {
        $user = new User()->setEmail('known@example.com');
        $session = new Session(new MockArraySessionStorage());
        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $this->listener($this->entityManagerFinding($user), requestStack: $requestStack)->onLoginFailure($this->failureOnFirewall('main', Request::create('/login', Request::METHOD_POST, ['_username' => 'known@example.com'])));

        $this->assertSame(1, $user->getFailedLoginAttempts());
        $this->assertSame(1, $session->get('failed_login_count'));
    }

    public function testAFailedLoginOnAnAlreadyLockedAccountIsLoggedButNeitherCountedNorNotified(): void
    {
        $lockedUntil = new \DateTimeImmutable('+20 minutes');
        $user = new User()->setEmail('locked@example.com')->setFailedLoginAttempts(10)->setAccountLockedUntil($lockedUntil);
        $session = new Session(new MockArraySessionStorage());
        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);
        $notifier = $this->createMock(SecurityNotificationService::class);
        $notifier->expects($this->never())->method('notifyAccountLocked');
        $notifier->expects($this->never())->method('notifySuspiciousActivity');

        $this->listener($this->entityManagerFinding($user), requestStack: $requestStack, notifier: $notifier)->onLoginFailure($this->failureOnFirewall('main', Request::create('/login', Request::METHOD_POST, ['_username' => 'locked@example.com'])));

        $this->assertSame(10, $user->getFailedLoginAttempts());
        $this->assertSame($lockedUntil, $user->getAccountLockedUntil());
        $this->assertSame([SecurityLog::EVENT_FAILED_LOGIN], $this->persistedLogTypes(), 'The attempt is still audited, and no account_locked entry is added.');
        $this->assertSame(1, $session->get('failed_login_count'));
    }

    public function testTheTenthFailureLocksTheAccountAndNotifiesOnce(): void
    {
        $user = new User()->setEmail('about-to-lock@example.com')->setFailedLoginAttempts(9);
        $notifier = $this->createMock(SecurityNotificationService::class);
        $notifier->expects($this->once())->method('notifyAccountLocked')->with($user, 30);

        $this->listener($this->entityManagerFinding($user), notifier: $notifier)->onLoginFailure($this->failureOnFirewall('api_login', $this->jsonLoginRequest('about-to-lock@example.com')));

        $this->assertTrue($user->isAccountLocked());
        $this->assertSame([SecurityLog::EVENT_FAILED_LOGIN, SecurityLog::EVENT_ACCOUNT_LOCKED], $this->persistedLogTypes());
    }

    public function testAnOversizedEmailIsTruncatedInTheLogInsteadOfBreakingTheInsert(): void
    {
        $this->listener($this->entityManagerFinding(null))->onLoginFailure($this->failureOnFirewall('api_login', $this->jsonLoginRequest(str_repeat('a', 4000).'@example.com')));

        $this->assertInstanceOf(SecurityLog::class, $this->persisted[0]);
        $this->assertSame(255, mb_strlen($this->persisted[0]->getEmail()));
    }

    private function listener(
        EntityManagerInterface $entityManager,
        ?LoggerInterface $logger = null,
        ?RequestStack $requestStack = null,
        ?SecurityNotificationService $notifier = null,
    ): AuthenticationFailureListener {
        return new AuthenticationFailureListener(
            $entityManager,
            $logger ?? new NullLogger(),
            $requestStack ?? $this->createStub(RequestStack::class),
            $notifier ?? $this->createStub(SecurityNotificationService::class),
        );
    }

    /**
     * An entity manager whose user lookup returns $user and which records what gets persisted.
     *
     * @return EntityManagerInterface&MockObject
     */
    private function entityManagerFinding(?User $user): EntityManagerInterface
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });

        return $entityManager;
    }

    /**
     * @return list<string>
     */
    private function persistedLogTypes(): array
    {
        return array_map(
            static fn (object $entity): string => $entity instanceof SecurityLog ? $entity->getEventType() : $entity::class,
            $this->persisted,
        );
    }

    private function failureOnFirewall(string $firewall, Request $request): LoginFailureEvent
    {
        return new LoginFailureEvent(new BadCredentialsException(), $this->createStub(AuthenticatorInterface::class), $request, null, $firewall);
    }

    private function jsonLoginRequest(string $email): Request
    {
        return Request::create('/api/v1/auth/login', Request::METHOD_POST, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['email' => $email, 'password' => 'x'], \JSON_THROW_ON_ERROR));
    }
}
