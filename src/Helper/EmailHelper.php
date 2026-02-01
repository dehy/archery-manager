<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Club;
use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\LicenseeRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class EmailHelper
{
    public function __construct(
        private MailerInterface $mailer,
        private LicenseeRepository $licenseeRepository
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendWelcomeEmail(Licensee $licensee, Club $club): void
    {
        $email = (new TemplatedEmail())
            ->to($licensee->getUser()->getEmail())
            ->replyTo($club->getContactEmail())
            ->subject(\sprintf('%s - Bienvenue', $club->getName()))
            ->htmlTemplate(
                'licensee/mail_account_created.html.twig',
            )
            ->context([
                'licensee' => $licensee,
                'club' => $club,
            ]);

        $this->mailer->send($email);
    }

    public function sendLicenseesSyncResults(array $toEmails, array $syncResults): void
    {
        $count = \count($syncResults[SyncReturnValues::CREATED->value])
               + \count($syncResults[SyncReturnValues::UPDATED->value])
               + \count($syncResults[SyncReturnValues::REMOVED->value]);
        $added = $this->licenseeRepository->findBy(['fftaId' => $syncResults[SyncReturnValues::CREATED->value]]);
        $updated = $this->licenseeRepository->findBy(['fftaId' => $syncResults[SyncReturnValues::UPDATED->value]]);

        $to = array_map(static fn (User $user): Address => new Address($user->getEmail(), $user->getFullname()), $toEmails);
        $email = (new TemplatedEmail())
            ->to(...$to)
            ->subject('Synchronisation FFTA')
            ->text('test')
            ->htmlTemplate('email_notification/updated_licensees.txt.twig')
            ->textTemplate('email_notification/updated_licensees.txt.twig')
            ->locale('fr')
            ->context([
                'count' => $count,
                'added' => $added,
                'updated' => $updated,
            ]);

        $this->mailer->send($email);
    }
}
