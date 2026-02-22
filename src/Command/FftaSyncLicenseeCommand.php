<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Licensee;
use App\Helper\FftaHelper;
use App\Repository\LicenseeRepository;
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

#[AsCommand(
    name: 'app:ffta:sync-licensee',
    description: 'Sync licensee from the FFTA website',
),]
class FftaSyncLicenseeCommand extends Command
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
        $this->addArgument('licenseeCode', InputArgument::REQUIRED, 'Licensee Code');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, 'Specific season');
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
        $this->fftaHelper->setLogger(new ConsoleLogger($output));

        $licenseeCode = $input->getArgument('licenseeCode');
        $season = $input->getOption('season') ? (int) $input->getOption('season') : null;

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $this->entityManager->getRepository(Licensee::class);
        $licensee = $licenseeRepository->findOneByCode($licenseeCode);
        $license = $licensee->getLicenseForSeason($season);

        $licenseeId = $this->fftaHelper->getScrapper($license->getClub())->findLicenseeIdFromCode($licenseeCode);

        if (null === $licenseeId || 0 === $licenseeId) {
            $output->writeln(\sprintf('Licensee with code %s not found.', $licenseeCode));

            return Command::INVALID;
        }

        $this->fftaHelper->syncLicenseeWithId($license->getClub(), $licenseeId, $season);

        return Command::SUCCESS;
    }
}
