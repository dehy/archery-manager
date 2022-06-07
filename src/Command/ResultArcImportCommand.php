<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\Licensee;
use App\Entity\Result;
use App\Entity\Season;
use App\Repository\LicenseeRepository;
use App\Scrapper\FftaScrapper;
use App\Scrapper\ResultArcParser;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\EntityRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[
    AsCommand(
        name: "app:result-arc:import",
        description: "Add a short description for your command"
    )
]
class ResultArcImportCommand extends Command
{
    public function __construct(
        protected ResultArcParser $parser,
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("file", InputArgument::REQUIRED, "File to import");
        $this->addArgument("eventId", InputArgument::REQUIRED, "Event ID");
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getArgument("file");
        $eventId = $input->getArgument("eventId");

        $eventRepository = $this->entityManager->getRepository(Event::class);
        $event = $eventRepository->find($eventId);

        if (!$event->getContestType()) {
            $io->error(
                "You must set the contest type value of the event before importing results"
            );
            return Command::INVALID;
        }
        if (!$event->getDiscipline()) {
            $io->error(
                "You must set the event discipline before importing results"
            );
            return Command::INVALID;
        }

        if (!$event) {
            $io->error(sprintf("Event #%s not found", $eventId));
            return Command::INVALID;
        }

        $results = $this->parser->parseFile($file);

        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class
        );
        $resultRepository = $this->entityManager->getRepository(Result::class);

        foreach ($results as $fftaCode => $resultLine) {
            $licensee = $licenseeRepository->findByCode($fftaCode);
            if (!$licensee) {
                continue;
            }
            $existingResult = $resultRepository->findOneBy([
                "licensee" => $licensee->getId(),
                "event" => $event->getId(),
            ]);
            if ($existingResult) {
                $result = $existingResult;
            } else {
                $result = (new Result())
                    ->setEvent($event)
                    ->setLicensee($licensee);
            }
            list(
                $distance,
                $targetSize,
            ) = Result::distanceForContestTypeAndActivity(
                $event->getContestType(),
                $event->getDiscipline(),
                $resultLine->activity,
                $resultLine->ageCategory
            );
            $result
                ->setActivity($resultLine->activity)
                ->setDiscipline($event->getDiscipline())
                ->setDistance($distance)
                ->setTargetSize($targetSize)
                ->setScore($resultLine->score);

            $this->entityManager->persist($result);
        }
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
