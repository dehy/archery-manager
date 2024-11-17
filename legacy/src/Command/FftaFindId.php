<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Club;
use App\Repository\ClubRepository;
use App\Scrapper\FftaScrapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        protected readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('clubId', InputArgument::REQUIRED, 'Club Code');
        $this->addArgument(
            'code',
            InputArgument::REQUIRED,
            'FFTA Member Code',
        );
    }

    /**
     * @throws \Exception
     */
    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $clubId = $input->getArgument('clubId');
        $code = $input->getArgument('code');

        /** @var ClubRepository $clubRepository */
        $clubRepository = $this->entityManager->getRepository(Club::class);
        $club = $clubRepository->findOneByCode($clubId);
        $scrapper = new FftaScrapper($club);

        $id = $scrapper->findLicenseeIdFromCode($code);

        $io->success(\sprintf('Licensee %s has id #%s', $code, $id));

        return Command::SUCCESS;
    }
}
