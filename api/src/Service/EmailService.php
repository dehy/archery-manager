<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(string:default::MAILER_FROM_ADDRESS)%')]
        private readonly string $fromAddress = 'noreply@archerymanager.com',
    ) {
    }

    public function sendEmailVerification(User $user): void
    {
        if (!$user->email) {
            throw new \InvalidArgumentException('User must have an email address');
        }

        if (!$user->getEmailVerificationToken()) {
            throw new \InvalidArgumentException('User must have an email verification token');
        }

        $this->logger->info('Sending email verification', [
            'user_id' => $user->getId(),
            'email' => $user->email,
            'token' => substr($user->getEmailVerificationToken(), 0, 8) . '...',
        ]);

        try {
            $htmlContent = $this->twig->render('email/verification.html.twig', [
                'user' => $user,
                'verificationToken' => $user->getEmailVerificationToken(),
            ]);

            $email = (new Email())
                ->from($this->fromAddress)
                ->to($user->email)
                ->subject('Verify your email address')
                ->html($htmlContent);

            $this->mailer->send($email);

            $this->logger->info('Email verification sent successfully', [
                'user_id' => $user->getId(),
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email verification', [
                'user_id' => $user->getId(),
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
