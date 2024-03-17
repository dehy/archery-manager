<?php

namespace App\Service;

use App\DBAL\Types\RecurringType;
use App\Entity\Event;
use App\Entity\EventOccurrence;
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
     * @return EventOccurrence[]
     *
     * @throws \Exception
     */
    public function getEventOccurrencesForLicenseeFromDateToDate(
        Licensee $licensee,
        \DateTimeInterface $startDate = null,
        \DateTimeInterface $endDate = null,
    ): array {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository('App\Entity\Event');

        /** @var EventOccurrence[] $eventOccurrences */
        $eventOccurrences = [];

        /** @var Event[] $events */
        $events = $eventRepository->findForLicenseeFromDateToDate($licensee, $startDate, $endDate);
        foreach ($events as $event) {
            $occurrenceDates = $this->getRecurringOccurrences(
                $event,
                $startDate,
                $endDate,
            );
            foreach ($occurrenceDates as $occurrenceDate) {
                $eventOccurrence = (new EventOccurrence())
                    ->setEvent($event)
                    ->setOccurrenceDate($occurrenceDate);
                $eventOccurrences[] = $eventOccurrence;
            }
        }

        return $eventOccurrences;
    }

    /**
     * @return \DateTimeInterface[]
     */
    public function getRecurringOccurrences(
        Event $event,
        \DateTimeInterface $startDate = null,
        \DateTimeInterface $endDate = null,
    ): array {
        $eventStartDate = $event->getStartDate()->setTime(0, 0);
        $eventEndDate = $event->getEndDate()->setTime(0, 0);

        $startDate ??= $event->getStartDate()->setTime(0, 0);
        // If no end date is provided, retrieve only one year
        $endDate ??= $event->getEndDate()->setTime(0, 0) ??
            \DateTimeImmutable::createFromInterface($startDate)->modify('+ 1 year');

        if ($eventStartDate > $endDate || $eventEndDate < $startDate) {
            return [];
        }

        $occurrences = $startDate <= $eventStartDate ? [$eventStartDate] : [];
        $recurringPatterns = $event->getRecurringPatterns();

        foreach ($recurringPatterns as $pattern) {
            $currentDate = $eventStartDate;
            while ($currentDate > $startDate || $currentDate < $endDate) {
                $recurrenceItem = match ($pattern->getRecurringType()) {
                    RecurringType::WEEKLY => 'week',
                    RecurringType::DAILY => 'day',
                    RecurringType::MONTHLY => 'month',
                    RecurringType::YEARLY => 'year',
                };
                $currentDate = $currentDate->modify(
                    sprintf('+%s %s', $pattern->getSeparationCount() + 1, $recurrenceItem)
                );
                if ($currentDate < $startDate || $currentDate < $eventStartDate) {
                    continue;
                }
                if ($currentDate > $endDate || $currentDate > $eventEndDate) {
                    break;
                }
                $occurrences[] = clone $currentDate;
            }
        }

        return $occurrences;
    }
}
