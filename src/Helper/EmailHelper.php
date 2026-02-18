<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Club;
use App\Entity\ClubApplication;
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
        $email = new TemplatedEmail()
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

    /**
     * Notify the club that a new application has been submitted.
     *
     * @throws TransportExceptionInterface
     */
    public function sendClubApplicationNewEmail(ClubApplication $application): void
    {
        $club = $application->getClub();
        $licensee = $application->getLicensee();

        $email = new TemplatedEmail()
            ->to($club->getContactEmail())
            ->replyTo($licensee->getUser()->getEmail())
            ->subject(\sprintf('%s - Nouvelle demande d\'adhésion de %s %s', $club->getName(), $licensee->getFirstname(), $licensee->getLastname()))
            ->htmlTemplate('email/club_application_new.html.twig')
            ->context(['application' => $application]);

        $this->mailer->send($email);
    }

    /**
     * Notify the applicant their application was validated.
     *
     * @throws TransportExceptionInterface
     */
    public function sendClubApplicationValidatedEmail(ClubApplication $application): void
    {
        $licensee = $application->getLicensee();
        $club = $application->getClub();

        $email = new TemplatedEmail()
            ->to($licensee->getUser()->getEmail())
            ->replyTo($club->getContactEmail())
            ->subject(\sprintf('%s - Ta demande d\'adhésion a été acceptée !', $club->getName()))
            ->htmlTemplate('email/club_application_validated.html.twig')
            ->context(['application' => $application]);

        $this->mailer->send($email);
    }

    /**
     * Notify the applicant their application was placed on the waiting list.
     *
     * @throws TransportExceptionInterface
     */
    public function sendClubApplicationWaitingListEmail(ClubApplication $application): void
    {
        $licensee = $application->getLicensee();
        $club = $application->getClub();

        $email = new TemplatedEmail()
            ->to($licensee->getUser()->getEmail())
            ->replyTo($club->getContactEmail())
            ->subject(\sprintf('%s - Ta demande d\'adhésion est en liste d\'attente', $club->getName()))
            ->htmlTemplate('email/club_application_waiting_list.html.twig')
            ->context(['application' => $application]);

        $this->mailer->send($email);
    }

    /**
     * Notify the applicant their application was rejected.
     *
     * @throws TransportExceptionInterface
     */
    public function sendClubApplicationRejectedEmail(ClubApplication $application): void
    {
        $licensee = $application->getLicensee();
        $club = $application->getClub();

        $email = new TemplatedEmail()
            ->to($licensee->getUser()->getEmail())
            ->replyTo($club->getContactEmail())
            ->subject(\sprintf('%s - Réponse à ta demande d\'adhésion', $club->getName()))
            ->htmlTemplate('email/club_application_rejected.html.twig')
            ->context(['application' => $application]);

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
        $email = new TemplatedEmail()
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
