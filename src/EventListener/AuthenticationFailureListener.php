<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\SecurityLog;
use App\Entity\User;
use App\Service\SecurityNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class AuthenticationFailureListener implements EventSubscriberInterface
{
    private const int LOCKOUT_THRESHOLD = 10;

    // Lock account after 10 failed attempts
    private const int WARNING_THRESHOLD = 5;

    // Show CAPTCHA after 3 failed attempts in session
    private const int LOCKOUT_DURATION_MINUTES = 30; // Lock account for 30 minutes

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
        $request = $event->getRequest();
        $email = $request->request->get('_username', '');
        $ipAddress = $request->getClientIp() ?? 'unknown';
        $userAgent = $request->headers->get('User-Agent', '');

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

        // Track failed attempts in session for CAPTCHA logic
        $session = $this->requestStack->getSession();
        $sessionFailedCount = $session->get('failed_login_count', 0);
        $session->set('failed_login_count', $sessionFailedCount + 1);

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
}
