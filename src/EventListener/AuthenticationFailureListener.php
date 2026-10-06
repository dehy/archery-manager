<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\SecurityLog;
use App\Entity\User;
use App\Service\SecurityNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AccessTokenAuthenticator;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class AuthenticationFailureListener implements EventSubscriberInterface
{
    /**
     * Stateless firewalls of the mobile API: they have no session to keep a CAPTCHA counter in.
     */
    private const array API_FIREWALLS = ['api', 'api_login'];

    // Lock account after 10 failed attempts
    private const int LOCKOUT_THRESHOLD = 10;

    // Send a warning email after 5 failed attempts
    private const int WARNING_THRESHOLD = 5;

    // Lock account for 30 minutes
    private const int LOCKOUT_DURATION_MINUTES = 30;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly SecurityNotificationService $securityNotification,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        // A rejected bearer token is not a login attempt: don't log it nor count it against an account.
        if ($event->getAuthenticator() instanceof AccessTokenAuthenticator) {
            return;
        }

        $request = $event->getRequest();
        $email = $this->extractUsername($request);
        $ipAddress = $request->getClientIp() ?? 'unknown';
        $userAgent = $request->headers->get('User-Agent', '');

        // A throttled request was never checked against the password. It is not recorded in the
        // database: once throttled, every further request would otherwise write a row.
        if ($event->getException() instanceof TooManyLoginAttemptsAuthenticationException) {
            $this->logger->warning('Login attempt throttled', ['email' => $email, 'ip' => $ipAddress]);

            return;
        }

        // Try to find the user
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        // Create security log entry
        $securityLog = new SecurityLog();
        $securityLog->setUser($user);
        $securityLog->setEmail($email);
        $securityLog->setIpAddress($ipAddress);
        $securityLog->setEventType(SecurityLog::EVENT_FAILED_LOGIN);
        $securityLog->setUserAgent($userAgent);
        $securityLog->setDetails($event->getException()->getMessage());

        $this->entityManager->persist($securityLog);

        // Track failed attempts in session for CAPTCHA logic (the stateless API firewalls have no session)
        if (!\in_array($event->getFirewallName(), self::API_FIREWALLS, true)) {
            $session = $this->requestStack->getSession();
            $sessionFailedCount = $session->get('failed_login_count', 0);
            $session->set('failed_login_count', $sessionFailedCount + 1);
        }

        // An account that is already locked must neither have its lock extended nor re-send
        // the lockout email on every attempt: the failure is logged and that is all.
        if ($user instanceof User && $user->isAccountLocked()) {
            $this->entityManager->flush();

            return;
        }

        if (null !== $user) {
            // Increment failed login attempts
            $user->incrementFailedAttempts();

            $failedAttempts = $user->getFailedLoginAttempts();

            $this->logger->warning('Failed login attempt', [
                'email' => $email,
                'ip' => $ipAddress,
                'attempts' => $failedAttempts,
            ]);

            // Check if we need to lock the account
            if ($failedAttempts >= self::LOCKOUT_THRESHOLD) {
                $user->lockAccount(self::LOCKOUT_DURATION_MINUTES);

                // Log the lockout
                $lockLog = new SecurityLog();
                $lockLog->setUser($user);
                $lockLog->setEmail($email);
                $lockLog->setIpAddress($ipAddress);
                $lockLog->setEventType(SecurityLog::EVENT_ACCOUNT_LOCKED);
                $lockLog->setUserAgent($userAgent);
                $lockLog->setDetails('Account locked for '.self::LOCKOUT_DURATION_MINUTES.\sprintf(' minutes after %d failed attempts', $failedAttempts));

                $this->entityManager->persist($lockLog);

                $this->logger->alert('Account locked due to multiple failed login attempts', [
                    'email' => $email,
                    'ip' => $ipAddress,
                    'attempts' => $failedAttempts,
                ]);

                // Send account locked notification email
                $this->securityNotification->notifyAccountLocked($user, self::LOCKOUT_DURATION_MINUTES);
            } elseif (self::WARNING_THRESHOLD === $failedAttempts) {
                // Send warning email on 5th failed attempt
                $this->logger->warning('Suspicious activity detected - warning threshold reached', [
                    'email' => $email,
                    'ip' => $ipAddress,
                    'attempts' => $failedAttempts,
                ]);

                // Mark that we've notified about suspicious activity
                $user->setSuspiciousActivityNotifiedAt(new \DateTimeImmutable());

                // Log suspicious activity
                $suspiciousLog = new SecurityLog();
                $suspiciousLog->setUser($user);
                $suspiciousLog->setEmail($email);
                $suspiciousLog->setIpAddress($ipAddress);
                $suspiciousLog->setEventType(SecurityLog::EVENT_SUSPICIOUS_ACTIVITY);
                $suspiciousLog->setUserAgent($userAgent);
                $suspiciousLog->setDetails(\sprintf('Warning: %d failed login attempts detected', $failedAttempts));

                $this->entityManager->persist($suspiciousLog);

                // Send suspicious activity warning email
                $this->securityNotification->notifySuspiciousActivity($user, $failedAttempts);
            }

            $this->entityManager->flush();
        } else {
            // User not found, still log the attempt
            $this->logger->warning('Failed login attempt for non-existent user', [
                'email' => $email,
                'ip' => $ipAddress,
            ]);

            $this->entityManager->flush();
        }
    }

    /**
     * The web form posts `_username`; the mobile API posts a JSON body with `email`.
     */
    private function extractUsername(Request $request): string
    {
        $username = $request->request->get('_username');
        if (!\is_string($username) && 'json' === $request->getContentTypeFormat()) {
            $username = $this->emailFromJsonBody($request);
        }

        return \is_string($username) ? $username : '';
    }

    private function emailFromJsonBody(Request $request): ?string
    {
        try {
            $email = $request->toArray()['email'] ?? null;
        } catch (RequestExceptionInterface) {
            return null;
        }

        return \is_string($email) ? $email : null;
    }
}
