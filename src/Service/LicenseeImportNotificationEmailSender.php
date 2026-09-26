<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\LicenseeImportNotificationScenario;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class LicenseeImportNotificationEmailSender
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function send(User $user, LicenseeImportNotificationScenario $scenario): void
    {
        $email = new TemplatedEmail()
            ->from(new Address('noreply@admds.net', 'Les Archers de Guyenne'))
            ->to($user->getEmail())
            ->subject($scenario->emailSubject())
            ->htmlTemplate($scenario->emailTemplate())
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
