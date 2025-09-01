<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\Licensee;
use App\Helper\EventHelper;
use App\Repository\EventParticipationRepository;
use PHPUnit\Framework\TestCase;

final class EventHelperTest extends TestCase
{
    private EventParticipationRepository $eventParticipationRepository;
    private EventHelper $eventHelper;

    protected function setUp(): void
    {
        $this->eventParticipationRepository = $this->createMock(EventParticipationRepository::class);
        $this->eventHelper = new EventHelper($this->eventParticipationRepository);
    }

    public function testLicenseeParticipationToEventReturnsExistingParticipation(): void
    {
        $licensee = $this->createMock(Licensee::class);
        $event = $this->createMock(Event::class);
        $existingParticipation = $this->createMock(EventParticipation::class);

        $this->eventParticipationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'participant' => $licensee,
                'event' => $event,
            ])
            ->willReturn($existingParticipation);

        $result = $this->eventHelper->licenseeParticipationToEvent($licensee, $event);

        $this->assertSame($existingParticipation, $result);
    }

    public function testLicenseeParticipationToEventCreatesNewParticipation(): void
    {
        $licensee = $this->createMock(Licensee::class);
        $event = $this->createMock(Event::class);

        $this->eventParticipationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'participant' => $licensee,
                'event' => $event,
            ])
            ->willReturn(null);

        $result = $this->eventHelper->licenseeParticipationToEvent($licensee, $event);

        $this->assertInstanceOf(EventParticipation::class, $result);
        // Note: In a real application, we would test that setEvent and setParticipant are called,
        // but since we're dealing with a real EventParticipation object (not a mock),
        // we would need to verify the state differently or create a partial mock
    }

    public function testLicenseeParticipationToEventSetsCorrectEventAndParticipant(): void
    {
        $licensee = $this->createMock(Licensee::class);
        $event = $this->createMock(Event::class);

        $this->eventParticipationRepository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->eventHelper->licenseeParticipationToEvent($licensee, $event);

        // Verify it's a new EventParticipation instance
        $this->assertInstanceOf(EventParticipation::class, $result);
        
        // Since EventParticipation is a real entity, we can test the actual behavior
        $this->assertSame($event, $result->getEvent());
        $this->assertSame($licensee, $result->getParticipant());
    }

    public function testRepositoryIsCalledWithCorrectParameters(): void
    {
        $licensee = $this->createMock(Licensee::class);
        $event = $this->createMock(Event::class);

        $this->eventParticipationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->identicalTo([
                'participant' => $licensee,
                'event' => $event,
            ]));

        $this->eventHelper->licenseeParticipationToEvent($licensee, $event);
    }
}
