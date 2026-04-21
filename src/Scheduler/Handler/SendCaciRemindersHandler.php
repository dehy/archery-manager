<?php

declare(strict_types=1);

namespace App\Scheduler\Handler;

use App\Entity\License;
use App\Entity\Season;
use App\Entity\User;
use App\Repository\LicenseeAttachmentRepository;
use App\Scheduler\Message\SendCaciReminders;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendCaciRemindersHandler
{
    public function __construct(
        private LicenseeAttachmentRepository $attachmentRepository,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SendCaciReminders $message): void
    {
        $today = new \DateTimeImmutable();
        $currentSeason = Season::seasonForDate($today);
        $renewalDate = new \DateTimeImmutable($currentSeason.'-09-01');
        $threshold = $renewalDate->modify('-6 months');
        $campaignStart = new \DateTimeImmutable($currentSeason.'-06-01');
        $campaignEnd = new \DateTimeImmutable($currentSeason.'-08-31 23:59:59');

        $attachments = $this->attachmentRepository->findNeedingCaciReminder(
            $threshold,
            $campaignStart,
            $campaignEnd,
        );

        foreach ($attachments as $attachment) {
            $licensee = $attachment->getLicensee();

            $user = $licensee?->getUser();
            if (!$user instanceof User) {
                continue;
            }

            if (!$licensee->getLicenseForSeason($currentSeason) instanceof License) {
                continue;
            }

            $club = $licensee->getLicenseForSeason($currentSeason)?->getClub();

            $email = new TemplatedEmail()
                ->to($user->getEmail())
                ->subject('Pensez à renouveler votre CACI')
                ->textTemplate('email_notification/caci_reminder.txt.twig')
                ->context([
                    'licensee' => $licensee,
                    'renewalDate' => $renewalDate,
                    'club' => $club,
                ]);

            if (null !== $club?->getContactEmail()) {
                $email->replyTo($club->getContactEmail());
            }

            try {
                $this->mailer->send($email);
                $attachment->setLastCaciReminderSentAt($today);
            } catch (TransportExceptionInterface) {
                // Skip this licensee on mail error; will be retried next campaign run
            }
        }

        $this->entityManager->flush();
    }
}
