<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

readonly class AccountActivationEmailSender
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @throws ResetPasswordExceptionInterface
     */
    public function send(User $user): void
    {
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);

        $email = new TemplatedEmail()
            ->from(new Address('noreply@admds.net', 'Les Archers de Guyenne'))
            ->to($user->getEmail())
            ->subject('Créez votre mot de passe')
            ->htmlTemplate('email_notification/account_activation.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ]);

        $this->mailer->send($email);
    }
}
