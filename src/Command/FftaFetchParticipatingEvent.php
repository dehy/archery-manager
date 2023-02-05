<?php

namespace App\Command;

use App\DBAL\Types\DisciplineType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\Licensee;
use App\Entity\Result;
use App\Factory\ResultFactory;
use App\Repository\EventRepository;
use App\Repository\LicenseeRepository;
use App\Repository\ResultRepository;
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
        name: 'app:ffta:fetch-participating-events',
        description: 'Fetch and display the events our bowmen participated',
    ),
]
class FftaFetchParticipatingEvent extends Command
{
    public function __construct(
        protected readonly FftaScrapper $scrapper,
        protected EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'season',
            InputArgument::OPTIONAL,
            'Season of the events',
            date('Y'),
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);

        $season = $input->getArgument('season');

        $fftaEvents = $this->scrapper->fetchEvents($season);

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $this->entityManager->getRepository(
            Licensee::class,
        );

        /** @var ResultRepository $resultRepository */
        $resultRepository = $this->entityManager->getRepository(Result::class);
        foreach ($fftaEvents as $fftaEvent) {
            $io->text(
                sprintf(
                    '[%s%s] %s à %s du %s au %s',
                    DisciplineType::getReadableValue(
                        $fftaEvent->getDiscipline(),
                    ),
                    $fftaEvent->getSpecifics()
                        ? '/' . $fftaEvent->getSpecifics()
                        : '',
                    $fftaEvent->getName(),
                    $fftaEvent->getLocation(),
                    $fftaEvent->getFrom()->format('d/m/Y'),
                    $fftaEvent->getTo()->format('d/m/Y'),
                ),
            );

            if (
                !in_array($fftaEvent->getDiscipline(), [
                    DisciplineType::INDOOR,
                    DisciplineType::TARGET,
                ])
            ) {
                $io->text('  /!\ Event type is not supported. Skipping.');

                continue;
            }
            $supportedSpecifics = ['2X18M'];
            if (
                $fftaEvent->getSpecifics()
                && !in_array($fftaEvent->getSpecifics(), $supportedSpecifics)
            ) {
                $io->text(
                    sprintf(
                        '  /!\ Event has some not supported specifics (%s). Skipping',
                        $fftaEvent->getSpecifics(),
                    ),
                );

                continue;
            }

            $event = $eventRepository
                ->createQueryBuilder('e')
                ->where('e.startsAt >= :fromMorning')
                ->andWhere('e.startsAt <= :fromNight')
                ->andWhere('e.endsAt >= :toMorning')
                ->andWhere('e.endsAt <= :toNight')
                ->setParameters([
                    'fromMorning' => $fftaEvent->getFrom()->setTime(0, 0, 0),
                    'fromNight' => $fftaEvent->getFrom()->setTime(23, 59, 59),
                    'toMorning' => $fftaEvent->getTo()->setTime(0, 0, 0),
                    'toNight' => $fftaEvent->getTo()->setTime(23, 59, 59),
                ])
                ->getQuery()
                ->getOneOrNullResult();
            if (!$event) {
                $event = ContestEvent::fromFftaEvent($fftaEvent);
                $this->entityManager->persist($event);
            }
            // Fetch results
            $fftaResults = $this->scrapper->fetchFftaResultsForFftaEvent(
                $fftaEvent,
            );

            foreach ($fftaResults as $fftaResult) {
                $licensee = $licenseeRepository->findOneByCode(
                    $fftaResult->getLicense(),
                );
                $io->text(sprintf('  + %s', $licensee->getFullname()));
                $result = $resultRepository->findOneBy([
                    'licensee' => $licensee,
                    'event' => $event,
                ]);
                if (!$result) {
                    $result = ResultFactory::createFromEventLicenseeAndFftaResult(
                        $event,
                        $licensee,
                        $fftaResult,
                    );
                    $this->entityManager->persist($result);
                }
            }

            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
