<?php

namespace App\Tests\Unit;

use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\FreeTrainingEvent;
use App\Entity\TrainingEvent;
use App\Type\DisciplineType;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testEventCreation(): void
    {
        $event = new Event();
        $event->name = 'Test Event';
        $event->discipline = DisciplineType::Target;
        $event->startsAt = new \DateTimeImmutable('2025-09-15 10:00:00');
        $event->endsAt = new \DateTimeImmutable('2025-09-15 18:00:00');

        $this->assertEquals('Test Event', $event->name);
        $this->assertEquals(DisciplineType::Target, $event->discipline);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->startsAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->endsAt);
    }

    public function testContestEventInheritance(): void
    {
        $contestEvent = new ContestEvent();
        $contestEvent->name = 'Championship';

        $this->assertInstanceOf(Event::class, $contestEvent);
        $this->assertEquals('Championship', $contestEvent->name);
        $this->assertNotNull($contestEvent->results);
    }

    public function testTrainingEventInheritance(): void
    {
        $trainingEvent = new TrainingEvent();
        $trainingEvent->name = 'Training Session';

        $this->assertInstanceOf(Event::class, $trainingEvent);
        $this->assertEquals('Training Session', $trainingEvent->name);
    }

    public function testFreeTrainingEventInheritance(): void
    {
        $freeTrainingEvent = new FreeTrainingEvent();
        $freeTrainingEvent->name = 'Open Practice';

        $this->assertInstanceOf(Event::class, $freeTrainingEvent);
        $this->assertEquals('Open Practice', $freeTrainingEvent->name);
    }

    public function testEventToString(): void
    {
        $event = new Event();
        $event->name = 'Sample Event';
        $event->startsAt = new \DateTimeImmutable('2025-09-15 10:00:00');

        $expected = '15/09/2025 - Sample Event';
        $this->assertEquals($expected, (string) $event);
    }
}
