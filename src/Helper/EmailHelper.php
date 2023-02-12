<?php

namespace App\Helper;

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
    public function sendWelcomeEmail(Licensee $licensee): void
    {
        $email = (new TemplatedEmail())
            ->to($licensee->getUser()->getEmail())
            ->replyTo('lesarchersdeguyenne@gmail.com')
            ->subject('Bienvenue aux Archers de Guyenne')
            ->htmlTemplate(
                'licensee/mail_account_created.html.twig',
            )
            ->context([
                'licensee' => $licensee,
            ]);

        $this->mailer->send($email);
    }
}
