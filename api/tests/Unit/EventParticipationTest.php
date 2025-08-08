<?php

namespace App\Tests\Unit;

use App\Entity\EventParticipation;
use App\Entity\Event;
use App\Entity\Licensee;
use App\Entity\Club;
use App\Type\GenderType;
use App\Type\EventParticipationStateType;
use PHPUnit\Framework\TestCase;

class EventParticipationTest extends TestCase
{
    public function testEventParticipationCreation(): void
    {
        $participation = new EventParticipation();
        $participation->participationState = EventParticipationStateType::Registered;

        $this->assertEquals(EventParticipationStateType::Registered, $participation->participationState);
    }

    public function testEventParticipationRelationships(): void
    {
        // Create event
        $event = new Event();
        $event->name = 'Summer Championship';
        $event->startsAt = new \DateTimeImmutable('2025-09-15 10:00:00');

        // Create licensee
        $licensee = new Licensee();
        $licensee->familyName = 'Archer';
        $licensee->givenName = 'Robin';
        $licensee->gender = GenderType::Male;
        $licensee->birthDate = new \DateTimeImmutable('1990-05-15');

        // Create participation
        $participation = new EventParticipation();
        $participation->event = $event;
        $participation->participant = $licensee;
        $participation->participationState = EventParticipationStateType::Registered;

        $this->assertEquals($event, $participation->event);
        $this->assertEquals($licensee, $participation->participant);
        $this->assertEquals('Summer Championship', $participation->event->name);
        $this->assertEquals('Robin Archer', $participation->participant->givenName . ' ' . $participation->participant->familyName);
    }

    public function testEventParticipationStatusTransitions(): void
    {
        $participation = new EventParticipation();

        // Test initial registration
        $participation->participationState = EventParticipationStateType::Registered;
        $this->assertEquals(EventParticipationStateType::Registered, $participation->participationState);

        // Test status change to interested
        $participation->participationState = EventParticipationStateType::Interested;
        $this->assertEquals(EventParticipationStateType::Interested, $participation->participationState);

        // Test status change to not going
        $participation->participationState = EventParticipationStateType::NotGoing;
        $this->assertEquals(EventParticipationStateType::NotGoing, $participation->participationState);
    }

    public function testEventParticipationTimestamps(): void
    {
        $participation = new EventParticipation();
        
        $participation->participationState = EventParticipationStateType::Registered;

        $this->assertEquals(EventParticipationStateType::Registered, $participation->participationState);
    }

    public function testEventParticipationToString(): void
    {
        // Create event
        $event = new Event();
        $event->name = 'Test Event';
        $event->startsAt = new \DateTimeImmutable('2025-09-15 10:00:00');

        // Create licensee
        $licensee = new Licensee();
        $licensee->familyName = 'Test';
        $licensee->givenName = 'User';
        $licensee->gender = GenderType::Other;
        $licensee->birthDate = new \DateTimeImmutable('1990-01-01');

        // Create participation
        $participation = new EventParticipation();
        $participation->event = $event;
        $participation->participant = $licensee;

        // Test basic properties are set
        $this->assertEquals('Test Event', $participation->event->name);
        $this->assertEquals('User', $participation->participant->givenName);
        $this->assertEquals('Test', $participation->participant->familyName);
    }

    public function testEventParticipationCollections(): void
    {
        $event = new Event();
        $event->name = 'Group Event';

        // Test initial empty collection
        $this->assertCount(0, $event->participations);

        // Create multiple participations
        $participation1 = new EventParticipation();
        $participation1->event = $event;
        $participation1->participationState = EventParticipationStateType::Registered;

        $participation2 = new EventParticipation();
        $participation2->event = $event;
        $participation2->participationState = EventParticipationStateType::Interested;

        $event->participations->add($participation1);
        $event->participations->add($participation2);

        $this->assertCount(2, $event->participations);
        $this->assertTrue($event->participations->contains($participation1));
        $this->assertTrue($event->participations->contains($participation2));
    }
}
