<?php

namespace App\Helper;

use App\Entity\Licensee;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class EmailHelper
{
    public function __construct(protected readonly MailerInterface $mailer, protected readonly ClubHelper $clubHelper)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendWelcomeEmail(Licensee $licensee): void
    {
        $club = $this->clubHelper->activeClubFor($licensee);
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
