<?php

namespace App\Command;

use App\Entity\Licensee;
use App\Scrapper\FftaScrapper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[
    AsCommand(
        name: "app:ffta:list-licensees",
        description: "List licensees from the FFTA"
    )
]
class FftaListLicenseesCommand extends Command
{
    public function __construct(protected readonly FftaScrapper $scrapper)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("season", InputArgument::REQUIRED, "Season");
    }

    /**
     * @throws Exception
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $season = $input->getArgument("season");

        $fftaIdentities = $this->scrapper->fetchLicenseeList($season);
        var_dump($fftaIdentities);

        return Command::SUCCESS;
    }
}
