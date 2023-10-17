<?php

namespace App\Helper;

use App\Entity\Club;
use App\Entity\Licensee;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class EmailHelper
{
    public function __construct(protected readonly MailerInterface $mailer)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendWelcomeEmail(Licensee $licensee, Club $club): void
    {
        $email = (new TemplatedEmail())
            ->to($licensee->getUser()->getEmail())
            ->replyTo($club->getContactEmail())
            ->subject(sprintf('%s - Bienvenue', $club->getName()))
            ->htmlTemplate(
                'licensee/mail_account_created.html.twig',
            )
            ->context([
                'licensee' => $licensee,
                'club' => $club,
            ]);

        $this->mailer->send($email);
    }
}
