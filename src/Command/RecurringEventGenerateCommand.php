<?php

namespace App\Command;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\FreeTrainingEvent;
use App\Entity\Group;
use App\Entity\HobbyContestEvent;
use App\Entity\TrainingEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recurring-event:generate',
    description: 'Add a short description for your command',
)]
class RecurringEventGenerateCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $nameQuestion = new Question('Titre : ');
        $name = $helper->ask($input, $output, $nameQuestion);

        $startsAtQuestion = new Question('Débute le : ');
        $startsAt = new \DateTimeImmutable($helper->ask($input, $output, $startsAtQuestion));

        $endsAtQuestion = new Question('Fini le : ');
        $endsAt = new \DateTimeImmutable($helper->ask($input, $output, $endsAtQuestion));

        $allDayQuestion = new ConfirmationQuestion('Toute la journée ? ', true);
        $allDay = $helper->ask($input, $output, $allDayQuestion);

        $addressQuestion = new Question('Adresse : ');
        $address = $helper->ask($input, $output, $addressQuestion);

        $kindQuestion = new ChoiceQuestion(
            'Type : ',
            EventType::getReadableValues(),
        );
        $kind = $helper->ask($input, $output, $kindQuestion);

        $disciplineQuestion = new ChoiceQuestion(
            'Discipline : ',
            DisciplineType::getReadableValues(),
        );
        $discipline = $helper->ask($input, $output, $disciplineQuestion);

        $groupRepository = $this->entityManager->getRepository(Group::class);
        $databaseGroups = $groupRepository->findAll();

        $assignedGroupsQuestion = new ChoiceQuestion('Groupes :', $databaseGroups);
        $assignedGroupsQuestion->setMultiselect(true);
        $assignedGroups = $helper->ask($input, $output, $assignedGroupsQuestion);

        $recurringQuestion = new ChoiceQuestion(
            'Récurrence :',
            ['Hebdomadaire'],
            0
        );
        $recurrence = $helper->ask($input, $output, $recurringQuestion);

        $stopsAtQuestion = new Question("S'arrêter après le : ");
        $stopsAt = new \DateTimeImmutable($helper->ask($input, $output, $stopsAtQuestion));
        $stopsAt->setTime(23, 59, 59);

        while ($startsAt <= $stopsAt) {
            $eventClass = match ($kind) {
                EventType::CONTEST_OFFICIAL => ContestEvent::class,
                EventType::CONTEST_HOBBY => HobbyContestEvent::class,
                EventType::TRAINING => TrainingEvent::class,
                EventType::FREE_TRAINING => FreeTrainingEvent::class,
                default => Event::class,
            };
            $event = new $eventClass();
            $event->setName($name)
                ->setAddress($address)
                ->setStartsAt($startsAt)
                ->setEndsAt($endsAt)
                ->setAllDay($allDay)
                ->setDiscipline($discipline);

            foreach ($assignedGroups as $assignedGroup) {
                $event->addAssignedGroup($assignedGroup);
            }

            if ('Hebdomadaire' === $recurrence) {
                $startsAt = $startsAt->modify('+7 days');
                $endsAt = $endsAt->modify('+7 days');
            }

            $output->writeln('+ new event: '.$event);

            $this->entityManager->persist($event);
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
