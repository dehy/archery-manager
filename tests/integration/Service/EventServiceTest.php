<?php

namespace App\Tests\integration\Service;

use App\Factory\EventFactory;
use App\Factory\LicenseFactory;
use App\Factory\UserFactory;
use App\Repository\EventRepository;
use App\Service\EventService;
use App\Tests\SecurityTrait;
use SlopeIt\ClockMock\ClockMock;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EventServiceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use SecurityTrait;

    public function testGetEventsWithInstancesForLicenseeFromDateToDate()
    {
        // 1. "Arrange"
        $license = LicenseFactory::createOne([
            'season' => 2024,
        ]);

        $event = EventFactory::new([
            'club' => $license->getClub(),
            'endDate' => new \DateTimeImmutable('2024-06-30T00:00:00+01:00'),
        ])->weeklyRecurrent()->create();

        // 2. "Act"
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $eventInstances = $eventService->getEventInstancesForLicenseeFromDateToDate(
            $license->getLicensee(),
            new \DateTimeImmutable('2024-04-01T00:00:00+01:00'),
            new \DateTimeImmutable('2024-04-30T00:00:00+01:00')
        );

        static::assertCount(4, $eventInstances);
        foreach ($eventInstances as $eventInstance) {
            static::assertSame($event->object(), $eventInstance->getEvent());
        }
    }

    /**
     * @throws \Exception
     */
    public function testGetEventInstancesForLicenseeFromNowWithCountLimit(): void
    {
        // 1. Arrange
        $license = LicenseFactory::createOne([
            'season' => 2024,
        ]);

        $training = EventFactory::new([
            'name' => 'Test Entrainement',
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-04-27'),
        ])->weeklyRecurrent()->create();
        $contest = EventFactory::createOne([
            'name' => 'Test Competition',
            'startDate' => new \DateTimeImmutable('2024-04-07'),
            'startTime' => new \DateTimeImmutable('10:30:00'),
            'endDate' => new \DateTimeImmutable('2024-04-07'),
            'endTime' => new \DateTimeImmutable('11:30:00'),
        ]);

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        ClockMock::freeze(new \DateTime('2024-04-01'));
        $events = $eventService->getEventInstancesForLicenseeFromNowWithCountLimit($license->getLicensee(), 5);
        ClockMock::reset();

        // 3. Assert
        static::assertCount(5, $events);
        static::assertEquals($training->getName(), $events[0]->getEvent()->getName());
        static::assertEquals('2024-04-06', $events[0]->getInstanceDate()->format('Y-m-d'));
        static::assertEquals($contest->getName(), $events[1]->getEvent()->getName());
        static::assertEquals('2024-04-07', $events[1]->getInstanceDate()->format('Y-m-d'));
        static::assertEquals($training->getName(), $events[2]->getEvent()->getName());
        static::assertEquals('2024-04-13', $events[2]->getInstanceDate()->format('Y-m-d'));
        static::assertEquals($training->getName(), $events[3]->getEvent()->getName());
        static::assertEquals('2024-04-20', $events[3]->getInstanceDate()->format('Y-m-d'));
        static::assertEquals($training->getName(), $events[4]->getEvent()->getName());
        static::assertEquals('2024-04-27', $events[4]->getInstanceDate()->format('Y-m-d'));
    }

    public function testGetEventInstancesOfNonRecurringEvent(): void
    {
        // 1. Arrange
        $event = EventFactory::createOne([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-03-16'),
        ]);

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $instances = $eventService->getEventInstances($event->object());

        // 3. Assert
        self::assertCount(1, $instances);
        self::assertEquals('2024-03-16', $instances[0]->getInstanceDate()->format('Y-m-d'));
    }

    public function testGetEventInstancesOfRecurringEventWithEndDate()
    {
        // 1. Arrange
        $event = EventFactory::new([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-04-27'),
        ])->weeklyRecurrent()->create();

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $instances = $eventService->getEventInstances($event->object());

        // 3. Assert
        self::assertCount(7, $instances);
        self::assertEquals('2024-03-16', $instances[0]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-23', $instances[1]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-30', $instances[2]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-06', $instances[3]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-13', $instances[4]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-20', $instances[5]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-27', $instances[6]->getInstanceDate()->format('Y-m-d'));
    }

    public function testGetEventInstancesOfRecurringEventWithMaxNumOfOccurrences()
    {
        // 1. Arrange
        $event = EventFactory::new([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => null,
        ])->weeklyRecurrent(4)->create();

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $instances = $eventService->getEventInstances($event->object());

        // 3. Assert
        self::assertCount(4, $instances);
        self::assertEquals('2024-03-16', $instances[0]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-23', $instances[1]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-30', $instances[2]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-06', $instances[3]->getInstanceDate()->format('Y-m-d'));
    }

    public function testGetEventInstancesOfRecurringEventWithEventStartingWithinWindowAndFinishingAfterWindow(): void
    {
        // 1. Arrange
        $windowStart = new \DateTimeImmutable('2024-03-01');
        $windowEnd = new \DateTimeImmutable('2024-03-31');

        $event = EventFactory::new([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-04-27'),
        ])->weeklyRecurrent()->create();

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $instances = $eventService->getEventInstances($event->object(), $windowStart, $windowEnd);

        // 3. Assert
        self::assertCount(3, $instances);
        self::assertEquals('2024-03-16', $instances[0]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-23', $instances[1]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-30', $instances[2]->getInstanceDate()->format('Y-m-d'));
    }

    public function testGetEventInstancesOfRecurringEventWithEventStartingBeforeWindowAndFinishingWithinWindow(): void
    {
        // 1. Arrange
        $windowStart = new \DateTimeImmutable('2024-04-01');
        $windowEnd = new \DateTimeImmutable('2024-04-30');

        $event = EventFactory::new([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-04-27'),
        ])->weeklyRecurrent()->create();

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $instances = $eventService->getEventInstances($event->object(), $windowStart, $windowEnd);

        // 3. Assert
        self::assertCount(4, $instances);
        self::assertEquals('2024-04-06', $instances[0]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-13', $instances[1]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-20', $instances[2]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-27', $instances[3]->getInstanceDate()->format('Y-m-d'));
    }

    public function testGetEventInstancesOfRecurringEventWithEventStartingBeforeWindowAndFinishingAfterWindow(): void
    {
        // 1. Arrange
        $windowStart = new \DateTimeImmutable('2024-04-01');
        $windowEnd = new \DateTimeImmutable('2024-04-30');

        $event = EventFactory::new([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-05-11'),
        ])->weeklyRecurrent()->create();

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $instances = $eventService->getEventInstances($event->object(), $windowStart, $windowEnd);

        // 3. Assert
        self::assertCount(4, $instances);
        self::assertEquals('2024-04-06', $instances[0]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-13', $instances[1]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-20', $instances[2]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-27', $instances[3]->getInstanceDate()->format('Y-m-d'));
    }

    public function testCancelEventInstanceOfRecurringEvent(): void
    {
        // 1. Arrange
        $event = EventFactory::new([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-04-27'),
        ])->weeklyRecurrent()->create();

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $this->login(UserFactory::createOne()->object());
        $eventService->cancel($event->object(), new \DateTimeImmutable('2024-04-13'));
        $instances = $eventService->getEventInstances($event->object());
        $this->logout();

        // 3. Assert
        self::assertCount(6, $instances);
        self::assertEquals('2024-03-16', $instances[0]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-23', $instances[1]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-30', $instances[2]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-06', $instances[3]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-20', $instances[4]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-27', $instances[5]->getInstanceDate()->format('Y-m-d'));
    }

    public function testCancelEvent(): void
    {
        // 1. Arrange
        $event = EventFactory::new([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-03-16'),
        ])->create();
        $eventId = $event->getId();

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);
        /** @var EventRepository $eventRepository */
        $eventRepository = $container->get(EventRepository::class);

        $this->login(UserFactory::createOne()->object());
        $eventService->cancel($event->object());
        $this->logout();

        $event = $eventRepository->find($eventId);

        // 3. Assert
        self::assertNull($event);
    }

    public function testRescheduleOneEventInstance(): void
    {
        // 1. Arrange
        $event = EventFactory::new([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-04-27'),
        ])->weeklyRecurrent()->create();

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $this->login(UserFactory::createOne()->object());
        $eventService->rescheduleOneInstance(
            $event->object(),
            new \DateTimeImmutable('2024-04-13'),
            new \DateTimeImmutable('2024-04-14'),
            null,
            new \DateTimeImmutable('2024-04-14'),
        );
        $this->logout();

        // 3. Assert
        $instances = $eventService->getEventInstances($event->object());

        self::assertCount(7, $instances);
        self::assertEquals('2024-03-16', $instances[0]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-23', $instances[1]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-30', $instances[2]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-06', $instances[3]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-14', $instances[4]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-20', $instances[5]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-27', $instances[6]->getInstanceDate()->format('Y-m-d'));
    }

    public function testRescheduleAllFutureEventInstances(): void
    {
        // 1. Arrange
        $event = EventFactory::new([
            'startDate' => new \DateTimeImmutable('2024-03-16'),
            'endDate' => new \DateTimeImmutable('2024-04-27'),
        ])->weeklyRecurrent()->create();

        // 2. Act
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $this->login(UserFactory::createOne()->object());
        $rescheduledEvent = $eventService->rescheduleAllFutureInstances(
            $event->object(),
            new \DateTimeImmutable('2024-04-13'),
            new \DateTimeImmutable('2024-04-14'),
            null,
            new \DateTimeImmutable('2024-04-28'),
        );
        $this->logout();

        // 3. Assert
        $eventInstances = $eventService->getEventInstances($event->object());
        self::assertCount(4, $eventInstances);
        self::assertEquals('2024-03-16', $eventInstances[0]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-23', $eventInstances[1]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-03-30', $eventInstances[2]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-06', $eventInstances[3]->getInstanceDate()->format('Y-m-d'));

        $newEventInstances = $eventService->getEventInstances($rescheduledEvent);
        self::assertCount(3, $newEventInstances);
        self::assertEquals('2024-04-14', $newEventInstances[0]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-21', $newEventInstances[1]->getInstanceDate()->format('Y-m-d'));
        self::assertEquals('2024-04-28', $newEventInstances[2]->getInstanceDate()->format('Y-m-d'));
    }
}
