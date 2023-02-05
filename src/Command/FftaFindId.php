<?php

namespace App\Command;

use App\Scrapper\FftaScrapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[
    AsCommand(
        name: 'app:ffta:find-id',
        description: 'Find licensee ID from various data',
    ),
]
class FftaFindId extends Command
{
    public function __construct(
        protected readonly FftaScrapper $scrapper,
        protected readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'code',
            null,
            InputOption::VALUE_REQUIRED,
            'FFTA Member Code',
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

        $code = $input->getOption('code');

        if (!$code) {
            $io->error(
                'You should provide a FFTA member code with the `--code <code>` option',
            );
        }

        $id = $this->scrapper->findLicenseeIdFromCode($code);

        $io->success(sprintf('Licensee %s has id #%s', $code, $id));

        return Command::SUCCESS;
    }
}
