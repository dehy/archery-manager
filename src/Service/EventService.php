<?php

namespace App\Service;

use App\DBAL\Types\RecurringType;
use App\Entity\Event;
use App\Entity\EventInstance;
use App\Entity\EventInstanceException;
use App\Entity\Licensee;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class EventService
{
    public function __construct(
        readonly private EntityManagerInterface $entityManager,
        readonly private Security $security
    ) {
    }

    public function save(Event $event): Event
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository('App\Entity\Event');

        $eventRepository->add($event);

        return $event;
    }

    public function cancel(Event $event, \DateTimeInterface $instanceDateToDelete = null): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository('App\Entity\Event');
        $currentUser = $this->security->getUser();

        if (!$event->isRecurring()) {
            $eventRepository->remove($event);
        }
        if ($event->isRecurring()) {
            if (null === $instanceDateToDelete) {
                throw new \LogicException('You should provide an instance date to cancel this recurring event instance');
            }
            foreach ($this->getEventInstances($event) as $eventInstance) {
                if ($eventInstance->getInstanceDate()->format('Y-m-d') == $instanceDateToDelete->format('Y-m-d')) {
                    $instanceException = (new EventInstanceException())
                        ->setEvent($event)
                        ->setInstanceDate($eventInstance->getInstanceDate())
                        ->setIsRescheduled(false)
                        ->setIsCancelled(true)
                        ->setCreatedAt(new \DateTimeImmutable())
                        ->setCreatedBy($currentUser)
                    ;
                    $event->addEventInstanceException($instanceException);
                }
            }
        }

        $this->entityManager->flush();
    }

    public function rescheduleOneInstance(
        Event $event,
        \DateTimeImmutable $instanceDateToReschedule,
        \DateTimeImmutable $newStartDate = null,
        \DateTimeImmutable $newStartTime = null,
        \DateTimeImmutable $newEndDate = null,
        \DateTimeImmutable $newEndTime = null,
        bool $newIsFullDayEvent = null,
    ): EventInstanceException {
        $eventInstances = $this->getEventInstances($event);
        foreach ($eventInstances as $eventInstance) {
            if ($eventInstance->getInstanceDate()->format('Y-m-d') === $instanceDateToReschedule->format('Y-m-d')) {
                $eventInstanceException = (new EventInstanceException())
                    ->setEvent($event)
                    ->setIsRescheduled(true)
                    ->setIsCancelled(false)
                    ->setInstanceDate($instanceDateToReschedule)
                    ->setStartDate($newStartDate)
                    ->setStartTime($newStartTime)
                    ->setEndDate($newEndDate)
                    ->setEndTime($newEndTime)
                    ->setIsFullDayEvent($newIsFullDayEvent)
                    ->setCreatedBy($this->security->getUser())
                    ->setCreatedAt(new \DateTimeImmutable())
                ;

                $this->entityManager->persist($eventInstanceException);
                $this->entityManager->flush();

                return $eventInstanceException;
            }
        }

        throw new \LogicException(sprintf('Cannot find the instance date to reschedule (%s) for the Event #%s', $instanceDateToReschedule, $event->getId()));
    }

    public function rescheduleAllFutureInstances(
        Event $event,
        \DateTimeImmutable $instanceDateToReschedule,
        \DateTimeImmutable $newStartDate,
        \DateTimeImmutable $newStartTime = null,
        \DateTimeImmutable $newEndDate = null,
        \DateTimeImmutable $newEndTime = null,
        bool $newIsFullDayEvent = null,
    ): Event {
        if (!$event->isRecurring()) {
            throw new \LogicException('Only recurring events can be rescheduled');
        }

        // Create a new Event with different scheduling
        $rescheduledEvent = (clone $event)
            ->setParentEvent($event)
            ->setStartDate($newStartDate)
            ->setStartTime($newStartTime ?? $event->getStartTime())
            ->setEndDate($newEndDate ?? $event->getEndDate())
            ->setEndTime($newEndTime ?? $event->getEndTime())
        ;
        // Copy the recurring pattern with the new date
        // TODO create those recurring patterns during cloning
        foreach ($event->getRecurringPatterns() as $recurringPattern) {
            $rescheduledRecurringPattern = (clone $recurringPattern)
                ->setEvent($rescheduledEvent);
            switch ($rescheduledRecurringPattern->getRecurringType()) {
                case RecurringType::WEEKLY:
                    $rescheduledRecurringPattern->setDayOfWeek($rescheduledEvent->getStartDate()->format('N'));
                    break;
                case RecurringType::MONTHLY:
                    $rescheduledRecurringPattern->setDayOfMonth($rescheduledEvent->getStartDate()->format('j'));
                    break;
                case RecurringType::YEARLY:
                    $rescheduledRecurringPattern->setDayOfMonth($rescheduledEvent->getStartDate()->format('j'));
                    $rescheduledRecurringPattern->setMonthOfYear($rescheduledEvent->getStartDate()->format('m'));
                    break;
                case RecurringType::DAILY:
                default:
                    break;
            }
            $rescheduledEvent->addRecurringPattern($rescheduledRecurringPattern);
        }
        // TODO add assignedGroups
        // TODO add attachments
        $this->entityManager->persist($rescheduledEvent);

        // Update the current Event
        $eventInstances = $this->getEventInstances($event);

        // Find the last event instance before the split
        $lastEventInstance = $event->getStartDate();
        foreach ($eventInstances as $eventInstance) {
            if ($instanceDateToReschedule->format('Y-m-d') === $eventInstance->getInstanceDate()->format('Y-m-d')) {
                break;
            }
            $lastEventInstance = $eventInstance;
        }
        // Set this last event instance date as endDate
        $event->setEndDate(clone $lastEventInstance->getInstanceDate());
        // TODO remove event instance exceptions after the endDate from the current event

        $this->entityManager->flush();

        return $rescheduledEvent;
    }

    public function getEventInstances(
        Event $event,
        \DateTimeImmutable $windowStart = null,
        \DateTimeImmutable $windowEnd = null
    ): array {
        /** @var EventInstance[] $eventInstances */
        $eventInstances = [];

        $instanceDates = $this->getRecurringInstancesDates(
            $event,
            $windowStart,
            $windowEnd,
        );

        // Handle rescheduled instances
        $rescheduledInstances = $event->getEventInstanceExceptions()->filter(
            function (EventInstanceException $exception) {
                return $exception->isRescheduled();
            }
        );
        $rescheduledInstancesByDate = [];
        foreach ($rescheduledInstances as $rescheduledInstance) {
            $rescheduledInstancesByDate[$rescheduledInstance->getInstanceDate()->format('Y-m-d')] = $rescheduledInstance;
        }

        // Handle cancelled instances
        $cancelledInstances = $event->getEventInstanceExceptions()->filter(
            function (EventInstanceException $exception) {
                return $exception->isCancelled();
            }
        );
        $cancelledInstancesByDate = [];
        foreach ($cancelledInstances as $cancelledInstance) {
            $cancelledInstancesByDate[$cancelledInstance->getInstanceDate()->format('Y-m-d')] = $cancelledInstance;
        }

        foreach ($instanceDates as $instanceDate) {
            if (isset($cancelledInstancesByDate[$instanceDate->format('Y-m-d')])) {
                continue;
            }
            if (isset($rescheduledInstancesByDate[$instanceDate->format('Y-m-d')])) {
                $instanceDate = $rescheduledInstancesByDate[$instanceDate->format('Y-m-d')]->getStartDate();
            }
            $eventInstance = (new EventInstance())
                ->setEvent($event)
                ->setInstanceDate($instanceDate);
            $eventInstances[] = $eventInstance;
        }

        return $eventInstances;
    }

    /**
     * @return EventInstance[]
     *
     * @throws \Exception
     */
    public function getEventInstancesForLicenseeFromDateToDate(
        Licensee $licensee,
        \DateTimeImmutable $windowStart = null,
        \DateTimeImmutable $windowEnd = null,
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
     * @return EventInstance[]
     *
     * @throws \Exception
     */
    public function getEventInstancesForLicenseeFromNowWithCountLimit(
        Licensee $licensee,
        int $count,
    ): array {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository('App\Entity\Event');

        /** @var EventInstance[] $eventInstances */
        $eventInstances = [];

        /** @var Event[] $events */
        $events = $eventRepository->findForLicenseeSinceDate($licensee, new \DateTime());
        foreach ($events as $event) {
            $eventInstances = [...$eventInstances, ...$this->getEventInstances($event)];
        }

        usort($eventInstances, function (EventInstance $a, EventInstance $b) {
            return $a->getInstanceDate() <=> $b->getInstanceDate();
        });

        $selectedEventInstances = [];
        $now = new \DateTimeImmutable();
        foreach ($eventInstances as $eventInstance) {
            if (\count($selectedEventInstances) >= $count) {
                break;
            }
            if ($eventInstance->getInstanceDate() < $now
            && $eventInstance->getEvent()->getStartTime() < $now) {
                continue;
            }
            $selectedEventInstances[] = $eventInstance;
        }

        return $selectedEventInstances;
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
