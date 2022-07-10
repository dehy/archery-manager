<?php

namespace App\Command;

use App\Entity\Licensee;
use App\Scrapper\FftaScrapper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
        $this->addOption(
            "season",
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            "Season Filter"
        );
        $this->addOption(
            "all",
            null,
            InputOption::VALUE_REQUIRED,
            "Fetch for all licensee"
        );
        $this->addOption(
            "id",
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            "FFTA ID"
        );
        $this->addOption(
            "code",
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            "FFTA Member Code"
        );
    }

    /**
     * @throws Exception
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class
        );
        $seasons = new ArrayCollection($input->getOption("season"));
        $all = $input->getOption("all");
        $fftaIds = new ArrayCollection($input->getOption("id"));
        $fftaCodes = new ArrayCollection($input->getOption("code"));

        foreach ($fftaIds as $id) {
            $id = (int) $id;
            $identity = $this->scrapper->fetchLicenseeProfile($id);
            $fftaLicences = $this->scrapper->fetchLicenseeLicenses($id);
            $licensee = $licenseeRepository->findOneBy(["fftaId" => $id]);
            if (!$licensee) {
                $licensee = new Licensee();
                $licensee->setFirstname($identity->prenom);
                $licensee->setLastname($identity->nom);
                $licensee->setGender($identity->sexe);
                $licensee->setFftaId($id);
                $licensee->setFftaMemberCode($identity->codeAdherent);
                $licensee->setBirthdate($identity->dateNaissance);
            }
            if ($licensee) {
                foreach ($fftaLicences as $fftaLicence) {
                    if (
                        !$seasons->isEmpty() &&
                        !$seasons->contains((string) $fftaLicence->getSeason())
                    ) {
                        continue;
                    }

                    $season = $fftaLicence->getSeason();
                    $existingLicence = $licensee->getLicenseForSeason($season);
                    if ($existingLicence) {
                        $io->warning(
                            sprintf(
                                "Found for licensee #%s in %s. Merging.",
                                $id,
                                $season
                            )
                        );
                        $existingLicence->merge($fftaLicence);
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
