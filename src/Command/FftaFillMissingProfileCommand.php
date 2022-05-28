<?php

namespace App\Command;

use App\Entity\User;
use App\Scrapper\FftaScrapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[
    AsCommand(
        name: "app:ffta:fill-missing-profile",
        description: "Fill missing parts of a user with FFTA data"
    )
]
class FftaFillMissingProfileCommand extends Command
{
    public function __construct(
        protected readonly FftaScrapper $scrapper,
        protected readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("fftaId", InputArgument::IS_ARRAY, "FFTA ID");
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $userRepository = $this->entityManager->getRepository(User::class);
        $fftaIds = $input->getArgument("fftaId");

        foreach ($fftaIds as $id) {
            $identity = $this->scrapper->fetchLicenceeIdentity($id);
            $io->writeln(
                sprintf("\"%s\",\"%s\"", $identity->email, $identity->mobile)
            );
            sleep(1);
        }

        return Command::SUCCESS;
    }
}
