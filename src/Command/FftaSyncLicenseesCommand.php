<?php

namespace App\Command;

use App\Helper\FftaHelper;
use App\Scrapper\FftaScrapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

#[
    AsCommand(
        name: 'app:ffta:sync-licensees',
        description: 'Sync licensees from the FFTA website',
    ),
]
class FftaSyncLicenseesCommand extends Command
{
    public function __construct(
        protected readonly FftaScrapper $scrapper,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MailerInterface $mailer,
        protected readonly FftaHelper $fftaHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, 'Season');
    }

    /**
     * @throws \Exception
     * @throws TransportExceptionInterface
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->fftaHelper->setLogger(new ConsoleLogger($output));

        $season = $input->getOption('season') ? (int) $input->getOption('season') : null;

        $this->fftaHelper->syncLicensees($season);

        return Command::SUCCESS;
    }
}
