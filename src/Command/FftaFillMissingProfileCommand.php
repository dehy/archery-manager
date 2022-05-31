<?php

namespace App\Command;

use App\Entity\Licensee;
use App\Entity\User;
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

        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class
        );
        $fftaIds = $input->getArgument("fftaId");

        foreach ($fftaIds as $id) {
            $id = (int) $id;
            $identity = $this->scrapper->fetchLicenceeIdentity($id);
            $fftaLicences = $this->scrapper->fetchLicenseeLicenses($id);
            $licensee = $licenseeRepository->findOneBy(["fftaId" => $id]);
            if ($licensee) {
                foreach ($fftaLicences as $fftaLicence) {
                    $existingLicence = $licensee->getLicenseForSeason(
                        $fftaLicence->getSeason()
                    );
                    if ($existingLicence) {
                        $io->warning(
                            sprintf(
                                "Existing license found for licensee #%s. Not merging.",
                                $id
                            )
                        );
                    } else {
                        $fftaLicence->setLicensee($licensee);
                        $this->entityManager->persist($fftaLicence);
                    }
                }
            } else {
                $io->warning(
                    sprintf("User with FFTA id #%s not found in database", $id)
                );
            }

            $io->success(
                sprintf(
                    "Saving licences for licensee %s",
                    $licensee->getFullname()
                )
            );
            $this->entityManager->flush();
            sleep(1);
        }

        return Command::SUCCESS;
    }
}
