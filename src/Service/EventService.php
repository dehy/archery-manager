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
                        ->setStartDate($eventInstance->getInstanceDate())
                        ->setEndDate($eventInstance->getInstanceDate())
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

    public function getEventInstances(
        Event $event,
        \DateTimeInterface $windowStart = null,
        \DateTimeInterface $windowEnd = null
    ): array {
        /** @var EventInstance[] $eventInstances */
        $eventInstances = [];

        $instanceDates = $this->getRecurringInstancesDates(
            $event,
            $windowStart,
            $windowEnd,
        );
        $cancelledInstances = $event->getEventInstanceExceptions()->filter(
            function (EventInstanceException $exception) {
                return $exception->isCancelled();
            }
        );
        $cancelledInstancesByDate = [];
        foreach ($cancelledInstances as $cancelledInstance) {
            $cancelledInstancesByDate[$cancelledInstance->getStartDate()->format('Y-m-d')] = $cancelledInstance;
        }

        foreach ($instanceDates as $instanceDate) {
            if (isset($cancelledInstancesByDate[$instanceDate->format('Y-m-d')])) {
                continue;
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
