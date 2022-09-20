<?php

namespace App\Command;

use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Factory\LicenseeFactory;
use App\Factory\UserFactory;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
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
        name: 'app:ffta:import-licensees',
        description: 'Import licensees from the FFTA website',
    ),
]
class FftaImportLicenseesCommand extends Command
{
    public function __construct(
        protected readonly FftaScrapper $scrapper,
        protected readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('season', InputArgument::REQUIRED, 'Season');
    }

    /**
     * @throws Exception
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $season = (int) $input->getArgument('season');

        $fftaIds = $this->scrapper->fetchLicenseeIdList($season);
        $io->info(
            sprintf('Found %s licensees in %s', count($fftaIds), $season),
        );

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class,
        );

        foreach ($fftaIds as $fftaId) {
            $io->writeln(sprintf('==== %s ====', $fftaId));
            $licensee = $licenseeRepository->findOneByFftaId($fftaId);
            if (!$licensee) {
                $licensee = $this->createLicenseeFromFftaId($fftaId);
                $io->writeln(
                    sprintf(
                        '+ New Licensee: %s (%s)',
                        $licensee->__toString(),
                        $licensee->getFftaMemberCode(),
                    ),
                );
                $this->entityManager->persist($licensee);
            } else {
                $io->writeln(
                    sprintf(
                        '~ Existing Licensee: %s (%s)',
                        $licensee->__toString(),
                        $licensee->getFftaMemberCode(),
                    ),
                );
            }

            $license = $licensee->getLicenseForSeason($season);
            if (!$license) {
                $license = $this->createLicenseForLicenseeAndSeason(
                    $licensee,
                    $season,
                );
                $io->writeln(sprintf('  + New License for: %s', $season));
                $license->setLicensee($licensee);
                $this->entityManager->persist($license);
            } else {
                $io->writeln(sprintf('  ~ Existing License for: %s', $season));
            }
            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }

    protected function createLicenseeFromFftaId(int $fftaId): Licensee
    {
        $fftaProfile = $this->scrapper->fetchLicenseeProfile($fftaId);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneByEmail($fftaProfile->getEmail());

        if (!$user) {
            $user = UserFactory::createFromFftaProfile($fftaProfile);
            $this->entityManager->persist($user);
        }

        $licensee = LicenseeFactory::createFromFftaProfile($fftaProfile);
        $licensee->setUser($user);

        return $licensee;
    }

    protected function createLicenseForLicenseeAndSeason(
        ?Licensee $licensee,
        int $seasonYear,
    ): License {
        $licenses = $this->scrapper->fetchLicenseeLicenses(
            $licensee->getFftaId(),
            $seasonYear,
        );

        return $licenses[$seasonYear];
    }
}
