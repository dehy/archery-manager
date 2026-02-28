<?php

declare(strict_types=1);

namespace App\Command;

use App\DBAL\Types\EventScopeType;
use App\Entity\Club;
use App\Entity\ContestEvent;
use App\Repository\ClubRepository;
use App\Repository\ContestEventRepository;
use App\Scrapper\FftaPublicCompetitionsScrapper;
use App\Scrapper\FftaPublicEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ffta:sync-public-competitions',
    description: 'Sync public FFTA competitions from ffta.fr into the shared event calendar',
)]
class FftaSyncPublicCompetitionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FftaPublicCompetitionsScrapper $scrapper,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'club-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit sync to a specific club ID. Defaults to all clubs with watched department codes.',
            )
            ->addOption(
                'season',
                null,
                InputOption::VALUE_OPTIONAL,
                'Season year to sync (e.g. 2026). Defaults to current season.',
                (int) date('Y'),
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $seasonYear = (int) $input->getOption('season');
        $start = new \DateTimeImmutable(\sprintf('%d-09-01', $seasonYear - 1));
        $end = new \DateTimeImmutable(\sprintf('%d-08-31', $seasonYear));

        /** @var ClubRepository $clubRepository */
        $clubRepository = $this->entityManager->getRepository(Club::class);

        $clubId = $input->getOption('club-id');
        if (null !== $clubId) {
            $club = $clubRepository->find((int) $clubId);
            if (!$club instanceof Club) {
                $io->error(\sprintf('Club with ID %d not found.', $clubId));

                return Command::FAILURE;
            }

            $clubs = [$club];
        } else {
            $clubs = $clubRepository->findAll();
        }

        /** @var ContestEventRepository $contestRepo */
        $contestRepo = $this->entityManager->getRepository(ContestEvent::class);

        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($clubs as $club) {
            $departmentCodes = $club->getWatchedDepartmentCodes();
            if ([] === $departmentCodes) {
                $io->text(\sprintf('Skipping club "%s" — no watched department codes configured.', $club->getName()));
                continue;
            }

            $io->section(\sprintf('Syncing club "%s" (deps: %s)', $club->getName(), implode(', ', $departmentCodes)));

            $fftaEvents = $this->scrapper->fetchCompetitions($departmentCodes, $start, $end);
            $io->text(\sprintf('Found %d competitions from FFTA.', \count($fftaEvents)));

            foreach ($fftaEvents as $fftaEvent) {
                $existing = $contestRepo->findOneByFftaEventId($fftaEvent->fftaEventId);

                if ($existing instanceof ContestEvent) {
                    $this->updateContest($existing, $fftaEvent);
                    $io->text(\sprintf('  ✓ Updated: [#%d] %s', $fftaEvent->fftaEventId, $fftaEvent->name));
                    ++$totalUpdated;
                } else {
                    $contest = ContestEvent::fromFftaPublicEvent($fftaEvent);
                    $contest->setScope($this->inferScope($fftaEvent));
                    $this->entityManager->persist($contest);
                    $io->text(\sprintf('  + Created: [#%d] %s (scope: %s)', $fftaEvent->fftaEventId, $fftaEvent->name, $contest->getScope()));
                    ++$totalCreated;
                }
            }

            $this->entityManager->flush();
        }

        $io->success(\sprintf('Sync complete. Created: %d, Updated: %d.', $totalCreated, $totalUpdated));

        return Command::SUCCESS;
    }

    private function updateContest(ContestEvent $contest, FftaPublicEvent $fftaEvent): void
    {
        $contest
            ->setName($fftaEvent->name)
            ->setStartsAt($fftaEvent->startsAt)
            ->setEndsAt($fftaEvent->endsAt)
            ->setAddress($fftaEvent->address);
        $contest
            ->setFftaComiteDepartemental($fftaEvent->comiteDepartemental)
            ->setFftaComiteRegional($fftaEvent->comiteRegional)
            ->setScope($this->inferScope($fftaEvent));
    }

    private function inferScope(FftaPublicEvent $fftaEvent): string
    {
        $name = strtolower($fftaEvent->name);
        $organizer = strtolower($fftaEvent->organizerName);
        $comiteReg = strtolower($fftaEvent->comiteRegional);

        if (
            str_contains($name, 'international')
            || str_contains($name, 'coupe du monde')
            || str_contains($name, 'world cup')
            || str_contains($name, 'champion du monde')
            || str_contains($name, 'championnat du monde')
            || str_contains($name, 'european')
            || str_contains($name, 'européen')
        ) {
            return EventScopeType::INTERNATIONAL;
        }

        if (
            str_contains($name, 'national')
            || str_contains($name, 'france')
            || str_contains($name, 'championnat de france')
        ) {
            return EventScopeType::NATIONAL;
        }

        if (
            str_contains($organizer, 'comite regional')
            || str_contains($organizer, 'comité régional')
            || str_contains($comiteReg, 'comite regional')
            || str_contains($comiteReg, 'comité régional')
        ) {
            return EventScopeType::REGIONAL;
        }

        return EventScopeType::DEPARTMENTAL;
    }
}
