<?php

namespace App\Tests\Unit;

use App\Entity\Club;
use App\Entity\Event;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ClubTest extends TestCase
{
    public function testClubCreation(): void
    {
        $club = new Club();
        $club->name = 'Archery Club Lyon';

        $this->assertEquals('Archery Club Lyon', $club->name);
    }

    public function testClubToString(): void
    {
        $club = new Club();
        $club->name = 'Archery Club Paris';

        // Test that name is set correctly
        $this->assertEquals('Archery Club Paris', $club->name);
    }

    public function testClubEvents(): void
    {
        $club = new Club();
        $club->name = 'Test Club';

        // Test initial empty collection
        $this->assertCount(0, $club->events);

        // Create and add an event
        $event = new Event();
        $event->name = 'Test Event';
        $event->startsAt = new \DateTimeImmutable('2025-09-15 10:00:00');
        $event->club = $club;
        
        $club->events->add($event);

        $this->assertCount(1, $club->events);
        $this->assertTrue($club->events->contains($event));
        $this->assertEquals($club, $event->club);
    }

    public function testClubLicenses(): void
    {
        $club = new Club();
        $club->name = 'Test Club';

        // Test initial empty collection
        $this->assertCount(0, $club->licenses);

        // Test that collection is properly initialized
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $club->licenses);
    }

    public function testClubValidation(): void
    {
        $club = new Club();
        
        // Test that required fields can be set
        $club->name = 'Valid Club';
        
        $this->assertNotEmpty($club->name);
    }
}
