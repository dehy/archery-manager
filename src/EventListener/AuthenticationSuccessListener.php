<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\SecurityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Logs successful authentication events for security monitoring.
 */
class AuthenticationSuccessListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            return;
        }

        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $ipAddress = $request->getClientIp() ?? 'unknown';
        $userAgent = $request->headers->get('User-Agent', '');

        // Create security log entry for successful login
        $securityLog = new SecurityLog();
        $securityLog->setUser($user);
        $securityLog->setEmail($user->getEmail());
        $securityLog->setIpAddress($ipAddress);
        $securityLog->setEventType(SecurityLog::EVENT_SUCCESS_LOGIN);
        $securityLog->setUserAgent($userAgent);
        $securityLog->setDetails('Successful login');

        $this->entityManager->persist($securityLog);
        $this->entityManager->flush();

        $this->logger->info('Successful login', [
            'email' => $user->getEmail(),
            'ip' => $ipAddress,
        ]);
    }

    /**
     * Log successful user registration.
     */
    public function logSuccessfulRegistration(User $user, string $ipAddress, string $userAgent): void
    {
        $securityLog = new SecurityLog();
        $securityLog->setUser($user);
        $securityLog->setEmail($user->getEmail());
        $securityLog->setIpAddress($ipAddress);
        $securityLog->setEventType(SecurityLog::EVENT_SUCCESS_REGISTRATION);
        $securityLog->setUserAgent($userAgent);
        $securityLog->setDetails('New account created');

        $this->entityManager->persist($securityLog);
        $this->entityManager->flush();

        $this->logger->info('Successful registration', [
            'email' => $user->getEmail(),
            'ip' => $ipAddress,
        ]);
    }

    /**
     * Log successful password reset.
     */
    public function logSuccessfulPasswordReset(User $user, string $ipAddress, string $userAgent): void
    {
        $securityLog = new SecurityLog();
        $securityLog->setUser($user);
        $securityLog->setEmail($user->getEmail());
        $securityLog->setIpAddress($ipAddress);
        $securityLog->setEventType(SecurityLog::EVENT_SUCCESS_PASSWORD_RESET);
        $securityLog->setUserAgent($userAgent);
        $securityLog->setDetails('Password successfully reset');

        $this->entityManager->persist($securityLog);
        $this->entityManager->flush();

        $this->logger->info('Successful password reset', [
            'email' => $user->getEmail(),
            'ip' => $ipAddress,
        ]);
    }

    /**
     * Log password reset request.
     */
    public function logPasswordResetRequested(?User $user, string $email, string $ipAddress, string $userAgent): void
    {
        $securityLog = new SecurityLog();
        $securityLog->setUser($user);
        $securityLog->setEmail($email);
        $securityLog->setIpAddress($ipAddress);
        $securityLog->setEventType(SecurityLog::EVENT_PASSWORD_RESET_REQUESTED);
        $securityLog->setUserAgent($userAgent);
        $securityLog->setDetails($user instanceof User ? 'Password reset email sent' : 'Password reset requested for non-existent email');

        $this->entityManager->persist($securityLog);
        $this->entityManager->flush();

        $this->logger->info('Password reset requested', [
            'email' => $email,
            'ip' => $ipAddress,
            'user_exists' => $user instanceof User,
        ]);
    }
}
