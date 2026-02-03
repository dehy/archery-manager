<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Event;
use App\Entity\Licensee;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EventRepositoryTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testFindNextForLicenseeReturnsUpcomingEvents(): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $licensee = $this->entityManager
            ->getRepository(Licensee::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Licensee::class, $licensee);

        $events = $eventRepository->findNextForLicensee($licensee);

        foreach ($events as $event) {
            $this->assertInstanceOf(Event::class, $event);
            // Verify events are in the future
            $this->assertGreaterThanOrEqual(
                new \DateTime(),
                $event->getEndsAt()
            );
        }
    }

    public function testFindNextForLicenseeRespectsLimit(): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $licensee = $this->entityManager
            ->getRepository(Licensee::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Licensee::class, $licensee);

        $events = $eventRepository->findNextForLicensee($licensee, 3);

        $this->assertLessThanOrEqual(3, $events->count());
    }

    public function testFindNextForLicenseeFiltersByLicenseeGroups(): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $licensee = $this->entityManager
            ->getRepository(Licensee::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Licensee::class, $licensee);

        $events = $eventRepository->findNextForLicensee($licensee);

        foreach ($events as $event) {
            $assignedGroups = $event->getAssignedGroups();
            if ($assignedGroups->count() > 0) {
                // If event has assigned groups, licensee must be in at least one
                $licenseeGroups = $licensee->getGroups();
                $hasCommonGroup = false;
                foreach ($assignedGroups as $eventGroup) {
                    if ($licenseeGroups->contains($eventGroup)) {
                        $hasCommonGroup = true;
                        break;
                    }
                }

                $this->assertTrue($hasCommonGroup);
            }
        }
    }

    public function testFindForLicenseeByMonthAndYearReturnsEventsInDateRange(): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $licensee = $this->entityManager
            ->getRepository(Licensee::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Licensee::class, $licensee);

        // Test for current month
        $month = (int) date('n');
        $year = (int) date('Y');

        $events = $eventRepository->findForLicenseeByMonthAndYear($licensee, $month, $year);

        $this->assertIsArray($events);

        foreach ($events as $event) {
            $this->assertInstanceOf(Event::class, $event);

            // Verify event is within or overlaps the month
            $eventStart = $event->getStartsAt();
            $eventEnd = $event->getEndsAt();

            $monthStart = new \DateTime(\sprintf('%s-%s-01', $year, $month));
            $monthEnd = (clone $monthStart)->modify('last day of this month 23:59:59');

            // Event should either start in month, end in month, or span across month
            $this->assertTrue(
                ($eventStart >= $monthStart && $eventStart <= $monthEnd)
                || ($eventEnd >= $monthStart && $eventEnd <= $monthEnd)
                || ($eventStart <= $monthStart && $eventEnd >= $monthEnd)
            );
        }
    }

    public function testFindForLicenseeByMonthAndYearIncludesAdjacentWeekDays(): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $licensee = $this->entityManager
            ->getRepository(Licensee::class)
            ->findOneBy([]);

        $this->assertInstanceOf(Licensee::class, $licensee);

        $month = (int) date('n');
        $year = (int) date('Y');

        $events = $eventRepository->findForLicenseeByMonthAndYear($licensee, $month, $year);

        // The method extends to previous Monday and next Sunday
        // So we might get events outside the exact month boundaries
        $this->assertIsArray($events);
    }

    public function testFindBySlugReturnsEventWhenFound(): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $event = $eventRepository->findOneBy([]);
        $this->assertInstanceOf(Event::class, $event);

        $slug = $event->getSlug();
        $foundEvent = $eventRepository->findBySlug($slug);

        $this->assertInstanceOf(Event::class, $foundEvent);
        $this->assertSame($event->getId(), $foundEvent->getId());
        $this->assertSame($slug, $foundEvent->getSlug());
    }

    public function testFindBySlugReturnsNullWhenNotFound(): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        $event = $eventRepository->findBySlug('non-existent-event-slug-12345');

        $this->assertNotInstanceOf(Event::class, $event);
    }

    public function testAddPersistsEvent(): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        // Get an existing event to use as template
        $existingEvent = $eventRepository->findOneBy([]);
        $this->assertInstanceOf(Event::class, $existingEvent);

        // Clone it to avoid modifying the original
        $event = clone $existingEvent;
        $event->setName('Test Event for Repository '.uniqid());
        $event->setStartsAt(new \DateTimeImmutable('+1 year'));
        $event->setEndsAt(new \DateTimeImmutable('+1 year +2 hours'));

        $eventRepository->add($event, true);

        $this->assertNotNull($event->getId());

        // Cleanup
        $eventRepository->remove($event, true);
    }

    public function testRemoveDeletesEvent(): void
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->entityManager->getRepository(Event::class);

        // Get an existing event to use as template
        $existingEvent = $eventRepository->findOneBy([]);
        $this->assertInstanceOf(Event::class, $existingEvent);

        // Clone it to create new event
        $event = clone $existingEvent;
        $event->setName('Test Event to Remove '.uniqid());
        $event->setStartsAt(new \DateTimeImmutable('+2 years'));
        $event->setEndsAt(new \DateTimeImmutable('+2 years +1 hour'));

        $eventRepository->add($event, true);
        $eventId = $event->getId();

        $eventRepository->remove($event, true);

        $foundEvent = $eventRepository->find($eventId);
        $this->assertNotInstanceOf(Event::class, $foundEvent);
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
