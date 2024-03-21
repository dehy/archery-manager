<?php

namespace App\Service;

use App\DBAL\Types\RecurringType;
use App\Entity\Event;
use App\Entity\EventInstance;
use App\Entity\EventInstanceException;
use App\Entity\Licensee;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

class EventService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function save(Event $event): Event
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository('App\Entity\Event');

        $eventRepository->add($event);

        return $event;
    }

    /**
     * @return EventInstance[]
     *
     * @throws \Exception
     */
    public function getEventInstancesForLicenseeFromDateToDate(
        Licensee $licensee,
        \DateTimeInterface $windowStart = null,
        \DateTimeInterface $windowEnd = null,
    ): array {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository('App\Entity\Event');

        /** @var EventInstance[] $eventInstances */
        $eventInstances = [];

        /** @var Event[] $events */
        $events = $eventRepository->findForLicenseeFromDateToDate($licensee, $windowStart, $windowEnd);
        foreach ($events as $event) {
            $eventInstances += $this->getEventInstances($event, $windowStart, $windowEnd);
        }

        return $eventInstances;
    }

    /**
     * @return \DateTimeImmutable[]
     */
    protected function getRecurringInstancesDates(
        Event $event,
        \DateTimeImmutable $windowStart = null,
        \DateTimeImmutable $windowEnd = null,
    ): array {
        $eventStartDate = $event->getStartDate()->setTime(0, 0);
        $eventEndDate = $event->getEndDate()?->setTime(0, 0);

        $windowStart ??= $event->getStartDate()->setTime(0, 0);
        // If no end date is provided, retrieve only one year
        $windowEnd ??= $event->getEndDate()?->setTime(0, 0) ??
            \DateTimeImmutable::createFromInterface($windowStart)->modify('+ 1 year');

        if ($eventStartDate > $windowEnd || (null !== $eventEndDate && $eventEndDate < $windowStart)) {
            return [];
        }

        $instances = $windowStart <= $eventStartDate ? [$eventStartDate] : [];
        $recurringPatterns = $event->getRecurringPatterns();

        foreach ($recurringPatterns as $pattern) {
            $currentDate = $eventStartDate;
            $maxNumOfOccurrences = $pattern->getMaxNumOfOccurrences();
            while ($currentDate > $windowStart || $currentDate < $windowEnd) {
                $recurrenceItem = match ($pattern->getRecurringType()) {
                    RecurringType::WEEKLY => 'week',
                    RecurringType::DAILY => 'day',
                    RecurringType::MONTHLY => 'month',
                    RecurringType::YEARLY => 'year',
                };
                $currentDate = $currentDate->modify(
                    sprintf('+%s %s', $pattern->getSeparationCount() + 1, $recurrenceItem)
                );
                if ($currentDate < $windowStart || $currentDate < $eventStartDate) {
                    continue;
                }
                if ($currentDate > $windowEnd
                    || (null !== $eventEndDate && $currentDate > $eventEndDate)
                    || (null !== $maxNumOfOccurrences && \count($instances) >= $maxNumOfOccurrences)
                ) {
                    break;
                }
                $instances[] = clone $currentDate;
            }
        }

        return $instances;
    }
}
