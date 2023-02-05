<?php

namespace App\Command;

use App\Repository\LicenseeRepository;
use App\Scrapper\FftaScrapper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[
    AsCommand(
        name: 'app:ffta:profile-picture',
        description: 'Find licensee ID from various data',
    ),
]
class FftaFetchProfilePicture extends Command
{
    public function __construct(
        protected readonly FftaScrapper $scrapper,
        protected readonly LicenseeRepository $licenseeRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('code', InputArgument::REQUIRED, 'FFTA member code');

        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Output to filename',
        );
    }

    /**
     * @throws \Exception
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $code = $input->getArgument('code');
        $output = $input->getOption('output');

        $licensee = $this->licenseeRepository->findOneByCode($code);
        if (!$licensee) {
            $io->error(sprintf('Licensee with code %s was not found in database', $code));

            return Command::INVALID;
        }

        $pictureAsString = $this->scrapper->fetchLicenseeProfilePicture($licensee->getFftaId());

        if (null === $pictureAsString) {
            $io->info('The licensee has no profile picture');

            return Command::SUCCESS;
        }

        if (null !== $output) {
            file_put_contents($output, $pictureAsString);

            return Command::SUCCESS;
        }

        $io->write($pictureAsString);

        return Command::SUCCESS;
    }
}
