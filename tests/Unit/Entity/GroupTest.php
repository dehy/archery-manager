<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Club;
use App\Entity\Event;
use App\Entity\Group;
use App\Entity\Licensee;
use PHPUnit\Framework\TestCase;

final class GroupTest extends TestCase
{
    public function testConstructorInitializesCollections(): void
    {
        $group = new Group();

        $this->assertCount(0, $group->getLicensees());
        $this->assertCount(0, $group->getEvents());
    }

    public function testToString(): void
    {
        $club = $this->createMock(Club::class);
        $club->method('__toString')->willReturn('Test Club - City');

        $group = new Group();
        $group->setClub($club);
        $group->setName('Beginners');

        $this->assertSame('Test Club - City - Beginners', (string) $group);
    }

    public function testSetAndGetClub(): void
    {
        $group = new Group();
        $club = $this->createMock(Club::class);

        $group->setClub($club);

        $this->assertSame($club, $group->getClub());
    }

    public function testSetAndGetName(): void
    {
        $group = new Group();
        $group->setName('Advanced Archers');

        $this->assertSame('Advanced Archers', $group->getName());
    }

    public function testSetAndGetDescription(): void
    {
        $group = new Group();
        $group->setDescription('For experienced archers');

        $this->assertSame('For experienced archers', $group->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $group = new Group();
        $group->setDescription(null);

        $this->assertNull($group->getDescription());
    }

    public function testAddAndRemoveLicensee(): void
    {
        $group = new Group();
        $licensee = $this->createMock(Licensee::class);

        $group->addLicensee($licensee);
        $this->assertCount(1, $group->getLicensees());
        $this->assertTrue($group->getLicensees()->contains($licensee));

        $group->removeLicensee($licensee);
        $this->assertCount(0, $group->getLicensees());
        $this->assertFalse($group->getLicensees()->contains($licensee));
    }

    public function testAddLicenseeDoesNotDuplicateLicensees(): void
    {
        $group = new Group();
        $licensee = $this->createMock(Licensee::class);

        $group->addLicensee($licensee);
        $group->addLicensee($licensee);

        $this->assertCount(1, $group->getLicensees());
    }

    public function testAddAndRemoveEvent(): void
    {
        $group = new Group();
        $event = $this->createMock(Event::class);
        $event->expects($this->once())
            ->method('addAssignedGroup')
            ->with($group);
        $event->expects($this->once())
            ->method('removeAssignedGroup')
            ->with($group);

        $group->addEvent($event);
        $this->assertCount(1, $group->getEvents());
        $this->assertTrue($group->getEvents()->contains($event));

        $group->removeEvent($event);
        $this->assertCount(0, $group->getEvents());
        $this->assertFalse($group->getEvents()->contains($event));
    }

    public function testAddEventDoesNotDuplicateEvents(): void
    {
        $group = new Group();
        $event = $this->createMock(Event::class);
        $event->expects($this->once())
            ->method('addAssignedGroup')
            ->with($group);

        $group->addEvent($event);
        $group->addEvent($event);

        $this->assertCount(1, $group->getEvents());
    }

    public function testRemoveEventOnlyCallsRemoveWhenEventExists(): void
    {
        $group = new Group();
        $event = $this->createMock(Event::class);
        $event->expects($this->once())
            ->method('addAssignedGroup');
        $event->expects($this->once())
            ->method('removeAssignedGroup');

        $group->addEvent($event);
        $group->removeEvent($event);
        // Second call should not trigger removeAssignedGroup again
        $group->removeEvent($event);
    }

    public function testFluentInterface(): void
    {
        $group = new Group();

        $result = $group
            ->setClub($this->createMock(Club::class))
            ->setName('Test Group')
            ->setDescription('Test description');

        $this->assertSame($group, $result);
    }
}
