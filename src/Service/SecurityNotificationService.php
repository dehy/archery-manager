<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Service for sending security-related email notifications.
 */
class SecurityNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * Send a warning email when suspicious activity is detected.
     */
    public function notifySuspiciousActivity(User $user, int $failedAttempts): void
    {
        $email = new TemplatedEmail()
            ->from(new Address('noreply@admds.net', 'Les Archers de Guyenne'))
            ->to($user->getEmail())
            ->subject('Activité suspecte détectée sur votre compte')
            ->htmlTemplate('email/security_warning.html.twig')
            ->context([
                'user' => $user,
                'failedAttempts' => $failedAttempts,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send notification when user account is locked.
     */
    public function notifyAccountLocked(User $user, int $lockDurationMinutes = 30): void
    {
        $email = new TemplatedEmail()
            ->from(new Address('noreply@admds.net', 'Les Archers de Guyenne'))
            ->to($user->getEmail())
            ->subject('Votre compte a été temporairement verrouillé')
            ->htmlTemplate('email/account_locked.html.twig')
            ->context([
                'user' => $user,
                'lockDurationMinutes' => $lockDurationMinutes,
                'lockedUntil' => $user->getAccountLockedUntil(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send notification when account is unlocked (by admin or automatically).
     */
    public function notifyAccountUnlocked(User $user): void
    {
        $email = new TemplatedEmail()
            ->from(new Address('noreply@admds.net', 'Les Archers de Guyenne'))
            ->to($user->getEmail())
            ->subject('Votre compte a été déverrouillé')
            ->htmlTemplate('email/account_unlocked.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
