<?php

namespace App\Command;

use App\DBAL\Types\EventType;
use App\Entity\Event;
use App\Repository\EventRepository;
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
        name: "app:ffta:fetch-participating-events",
        description: "Fetch and display the events our bowmen participated"
    )
]
class FftaFetchParticipatingEvent extends Command
{
    public function __construct(
        protected readonly FftaScrapper $scrapper,
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            "season",
            InputArgument::OPTIONAL,
            "Season of the events",
            date("Y")
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $season = $input->getArgument("season");

        $fftaEvent = $this->scrapper->fetchEvents($season);

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);
        foreach ($fftaEvent as $fftaEvent) {
            $io->text(
                sprintf(
                    "%s à %s du %s au %s",
                    $fftaEvent["name"],
                    $fftaEvent["location"],
                    $fftaEvent["from"]->format("d/m/Y"),
                    $fftaEvent["to"]->format("d/m/Y")
                )
            );
            $event = $eventRepository->findOneBy([
                "startsAt" => $fftaEvent["from"],
                "endsAt" => $fftaEvent["to"],
            ]);
            if (!$event) {
                $event = (new Event())
                    ->setName($fftaEvent["name"])
                    ->setStartsAt($fftaEvent["from"])
                    ->setEndsAt($fftaEvent["to"])
                    ->setAddress($fftaEvent["location"])
                    ->setType(EventType::CONTEST_OFFICIAL)
                    ->setDiscipline($fftaEvent["discipline"]);

                $this->entityManager->persist($event);
            }
        }
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
