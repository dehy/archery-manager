<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\DBAL\Types\DisciplineType;
use App\Entity\Club;
use App\Entity\Event;
use App\Entity\EventAttachment;
use App\Entity\EventParticipation;
use App\Entity\Group;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    private const string TEST_EVENT_NAME = 'Test Event';

    public function testConstructorInitializesCollections(): void
    {
        $event = new Event();

        $this->assertCount(0, $event->getParticipations());
        $this->assertCount(0, $event->getAttachments());
        $this->assertCount(0, $event->getAssignedGroups());
    }

    public function testToString(): void
    {
        $event = new Event();
        $event->setName(self::TEST_EVENT_NAME);
        $event->setStartsAt(new \DateTimeImmutable('2025-01-15 10:00:00'));

        $this->assertStringContainsString('15/01/2025', (string) $event);
        $this->assertStringContainsString(self::TEST_EVENT_NAME, (string) $event);
    }

    public function testSetAndGetClub(): void
    {
        $event = new Event();
        $club = $this->createStub(Club::class);

        $event->setClub($club);

        $this->assertSame($club, $event->getClub());
    }

    public function testSetAndGetName(): void
    {
        $event = new Event();
        $event->setName('Championship');

        $this->assertSame('Championship', $event->getName());
    }

    public function testSetAndGetDiscipline(): void
    {
        $event = new Event();
        $event->setDiscipline(DisciplineType::TARGET);

        $this->assertSame(DisciplineType::TARGET, $event->getDiscipline());
    }

    public function testSetAndGetAllDay(): void
    {
        $event = new Event();
        $event->setAllDay(true);

        $this->assertTrue($event->isAllDay());
    }

    public function testAllDayDefaultsToFalse(): void
    {
        $event = new Event();

        $this->assertFalse($event->isAllDay());
    }

    public function testSetAndGetStartsAt(): void
    {
        $event = new Event();
        $date = new \DateTimeImmutable('2025-01-15 10:00:00');

        $event->setStartsAt($date);

        $this->assertSame($date, $event->getStartsAt());
    }

    public function testSetAndGetEndsAt(): void
    {
        $event = new Event();
        $date = new \DateTimeImmutable('2025-01-15 18:00:00');

        $event->setEndsAt($date);

        $this->assertSame($date, $event->getEndsAt());
    }

    public function testSetAndGetAddress(): void
    {
        $event = new Event();
        $event->setAddress('123 Main Street, City');

        $this->assertSame('123 Main Street, City', $event->getAddress());
    }

    public function testSetAndGetSlug(): void
    {
        $event = new Event();
        $event->setSlug('test-event-2025');

        $this->assertSame('test-event-2025', $event->getSlug());
    }

    public function testSetAndGetLatitude(): void
    {
        $event = new Event();
        $event->setLatitude('48.8566');

        $this->assertSame('48.8566', $event->getLatitude());
    }

    public function testSetAndGetLongitude(): void
    {
        $event = new Event();
        $event->setLongitude('2.3522');

        $this->assertSame('2.3522', $event->getLongitude());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $event = new Event();
        $date = new \DateTimeImmutable('2025-01-15 12:00:00');

        $event->setUpdatedAt($date);

        $this->assertSame($date, $event->getUpdatedAt());
    }

    public function testAddAndRemoveParticipation(): void
    {
        $event = new Event();
        $participation = $this->createStub(EventParticipation::class);

        $event->addParticipation($participation);
        $this->assertCount(1, $event->getParticipations());
        $this->assertTrue($event->getParticipations()->contains($participation));

        $event->removeParticipation($participation);
        $this->assertCount(0, $event->getParticipations());
        $this->assertFalse($event->getParticipations()->contains($participation));
    }

    public function testAddParticipationDoesNotDuplicateParticipations(): void
    {
        $event = new Event();
        $participation = $this->createMock(EventParticipation::class);
        $participation->expects($this->once())
            ->method('setEvent');

        $event->addParticipation($participation);
        $event->addParticipation($participation);

        $this->assertCount(1, $event->getParticipations());
    }

    public function testAddAndRemoveAttachment(): void
    {
        $event = new Event();
        $attachment = $this->createStub(EventAttachment::class);

        $event->addAttachment($attachment);
        $this->assertCount(1, $event->getAttachments());
        $this->assertTrue($event->getAttachments()->contains($attachment));

        $event->removeAttachment($attachment);
        $this->assertCount(0, $event->getAttachments());
        $this->assertFalse($event->getAttachments()->contains($attachment));
    }

    public function testAddAttachmentDoesNotDuplicateAttachments(): void
    {
        $event = new Event();
        $attachment = $this->createMock(EventAttachment::class);
        $attachment->expects($this->once())
            ->method('setEvent');

        $event->addAttachment($attachment);
        $event->addAttachment($attachment);

        $this->assertCount(1, $event->getAttachments());
    }

    public function testAddAndRemoveAssignedGroup(): void
    {
        $event = new Event();
        $group = $this->createStub(Group::class);

        $event->addAssignedGroup($group);
        $this->assertCount(1, $event->getAssignedGroups());
        $this->assertTrue($event->getAssignedGroups()->contains($group));

        $event->removeAssignedGroup($group);
        $this->assertCount(0, $event->getAssignedGroups());
        $this->assertFalse($event->getAssignedGroups()->contains($group));
    }

    public function testAddAssignedGroupDoesNotDuplicateGroups(): void
    {
        $event = new Event();
        $group = $this->createStub(Group::class);

        $event->addAssignedGroup($group);
        $event->addAssignedGroup($group);

        $this->assertCount(1, $event->getAssignedGroups());
    }

    public function testFluentInterface(): void
    {
        $event = new Event();
        $date = new \DateTimeImmutable();

        $result = $event
            ->setClub($this->createStub(Club::class))
            ->setName(self::TEST_EVENT_NAME)
            ->setDiscipline(DisciplineType::INDOOR)
            ->setAllDay(false)
            ->setStartsAt($date)
            ->setEndsAt($date)
            ->setAddress('Test Address');

        $this->assertSame($event, $result);
    }
}
