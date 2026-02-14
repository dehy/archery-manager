<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\SecurityLog;
use App\Entity\User;
use App\Service\SecurityNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'security:unlock-account',
    description: 'Unlock a user account that was locked due to failed login attempts',
)]
class SecurityUnlockAccountCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SecurityNotificationService $securityNotification,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email address of the user to unlock');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        // Find the user
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            $io->error(\sprintf('User with email "%s" not found.', $email));

            return Command::FAILURE;
        }

        // Check if account is actually locked
        if (!$user->isAccountLocked()) {
            $io->info(\sprintf('Account "%s" is not currently locked.', $email));

            return Command::SUCCESS;
        }

        // Get locked until date for the log
        $lockedUntil = $user->getAccountLockedUntil();

        // Unlock the account
        $user->setAccountLockedUntil(null);
        $user->resetFailedAttempts();

        // Log the unlock event
        $securityLog = new SecurityLog();
        $securityLog->setUser($user);
        $securityLog->setEmail($user->getEmail());
        $securityLog->setIpAddress('CLI');
        $securityLog->setEventType(SecurityLog::EVENT_ACCOUNT_UNLOCKED);
        $securityLog->setUserAgent('console command');
        $securityLog->setDetails(\sprintf(
            'Account manually unlocked by admin. Was locked until: %s',
            $lockedUntil?->format('Y-m-d H:i:s') ?? 'unknown'
        ));

        $this->entityManager->persist($securityLog);
        $this->entityManager->flush();

        // Send notification email
        $this->securityNotification->notifyAccountUnlocked($user);

        $io->success(\sprintf(
            'Account "%s" has been unlocked successfully. Failed login attempts reset to 0.',
            $email
        ));

        return Command::SUCCESS;
    }
}
