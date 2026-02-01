<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Club;
use App\Helper\FftaHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:ffta:sync-licensees',
    description: 'Sync licensees from the FFTA website',
),]
class FftaSyncLicenseesCommand extends Command
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MailerInterface $mailer,
        protected readonly FftaHelper $fftaHelper,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('club_code', InputArgument::REQUIRED, 'Code Club');
        $this->addArgument('season', InputArgument::REQUIRED, 'Season');
    }

    /**
     * @throws \Exception
     * @throws TransportExceptionInterface
     */
    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $this->fftaHelper->setLogger(new ConsoleLogger($output));

        $fftaClubCode = $input->getArgument('club_code');
        $clubRepository = $this->entityManager->getRepository(Club::class);
        $club = $clubRepository->findOneBy(['fftaCode' => $fftaClubCode]);

        if ($club === null) {
            $io->error(\sprintf('Unknown club %s', $fftaClubCode));

            return Command::INVALID;
        }

        $season = (int) $input->getArgument('season');

        $this->fftaHelper->syncLicensees($club, $season);

        return Command::SUCCESS;
    }
}
