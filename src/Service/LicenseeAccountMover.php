<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Licensee;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Moves a licensee from the user account it is attached to onto another one.
 *
 * Everything hanging off the licensee (licenses, participations, results,
 * equipment, attachments) follows it, since it is keyed on the licensee.
 */
readonly class LicenseeAccountMover
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Creates a dedicated, unverified account for the licensee and moves them onto it.
     */
    public function moveToNewAccount(Licensee $licensee, string $email, bool $deleteEmptiedAccount): User
    {
        $user = new User();
        $user->setEmail(mb_strtolower(trim($email)));
        $user->setFirstname($licensee->getFirstname());
        $user->setLastname($licensee->getLastname());
        $user->setGender($licensee->getGender());
        if ($licensee->getBirthdate() instanceof \DateTimeInterface) {
            $user->setBirthdate(\DateTimeImmutable::createFromInterface($licensee->getBirthdate()));
        }

        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);

        $this->reassign($licensee, $user, $deleteEmptiedAccount);

        return $user;
    }

    public function moveToExistingAccount(Licensee $licensee, User $target, bool $deleteEmptiedAccount): void
    {
        $this->reassign($licensee, $target, $deleteEmptiedAccount);
    }

    /**
     * Sends the "create your password" email. Returns false (and logs) when it could not be sent,
     * so a mailer outage does not undo an already committed move.
     */
    public function sendInvitation(User $user): bool
    {
        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            $this->mailer->send(
                new TemplatedEmail()
                    ->from(new Address('noreply@admds.net', 'Les Archers de Guyenne'))
                    ->to($user->getEmail())
                    ->subject('Créez votre mot de passe — Archery Manager')
                    ->htmlTemplate('reset_password/email.html.twig')
                    ->context(['resetToken' => $resetToken]),
            );
        } catch (\Throwable $throwable) {
            $this->logger->error('Could not send account invitation', [
                'user_id' => $user->getId(),
                'exception' => $throwable,
            ]);

            return false;
        }

        return true;
    }

    private function reassign(Licensee $licensee, User $target, bool $deleteEmptiedAccount): void
    {
        $source = $licensee->getUser();

        if ($source === $target) {
            throw new \InvalidArgumentException('The licensee already belongs to this account.');
        }

        $licensee->setUser($target);

        if ($source instanceof User) {
            // Detach first: User::$licensees cascades remove, so deleting the old
            // account would otherwise delete the licensee that was just moved.
            $source->removeLicensee($licensee);

            if ($deleteEmptiedAccount && $source->getLicensees()->isEmpty()) {
                $this->entityManager->remove($source);
            }
        }

        $this->entityManager->flush();
    }
}
