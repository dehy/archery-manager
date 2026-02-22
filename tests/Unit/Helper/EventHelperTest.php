<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\DBAL\Types\EventParticipationStateType;
use App\DBAL\Types\LicenseActivityType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\Group;
use App\Entity\HobbyContestEvent;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\TrainingEvent;
use App\Helper\EventHelper;
use App\Repository\EventParticipationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventHelper::class)]
final class EventHelperTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $eventParticipationRepository;

    private EventHelper $eventHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventParticipationRepository = $this->createMock(EventParticipationRepository::class);
        $this->eventHelper = new EventHelper($this->eventParticipationRepository);
    }

    public function testLicenseeParticipationToEventReturnsExistingParticipation(): void
    {
        $licensee = $this->createStub(Licensee::class);
        $event = $this->createStub(Event::class);
        $existingParticipation = $this->createStub(EventParticipation::class);

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
        $licensee = $this->createStub(Licensee::class);
        $event = $this->createMock(Event::class);
        $event->method('getStartsAt')->willReturn(new \DateTimeImmutable('2025-01-15'));

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
        $licensee = $this->createStub(Licensee::class);
        $event = $this->createMock(Event::class);
        $event->method('getStartsAt')->willReturn(new \DateTimeImmutable('2025-01-15'));

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
        $licensee = $this->createStub(Licensee::class);
        $event = $this->createMock(Event::class);
        $event->method('getStartsAt')->willReturn(new \DateTimeImmutable('2025-01-15'));

        $this->eventParticipationRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with($this->identicalTo([
                'participant' => $licensee,
                'event' => $event,
            ]));

        $this->eventHelper->licenseeParticipationToEvent($licensee, $event);
    }

    public function testCanLicenseeParticipateInEventReturnsTrueWhenNoAssignedGroups(): void
    {
        $event = $this->createMock(TrainingEvent::class);
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection());

        $result = $this->eventHelper->canLicenseeParticipateInEvent($this->createStub(Licensee::class), $event);

        $this->assertTrue($result);
    }

    public function testCanLicenseeParticipateInEventReturnsTrueWhenLicenseeInAssignedGroup(): void
    {
        $group = $this->createStub(Group::class);
        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getGroups')->willReturn(new ArrayCollection([$group]));

        $event = $this->createMock(TrainingEvent::class);
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection([$group]));

        $result = $this->eventHelper->canLicenseeParticipateInEvent($licensee, $event);

        $this->assertTrue($result);
    }

    public function testCanLicenseeParticipateInEventReturnsFalseWhenLicenseeNotInAssignedGroup(): void
    {
        $group1 = $this->createStub(Group::class);
        $group2 = $this->createStub(Group::class);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getGroups')->willReturn(new ArrayCollection([$group1]));

        $event = $this->createMock(TrainingEvent::class);
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection([$group2]));

        $result = $this->eventHelper->canLicenseeParticipateInEvent($licensee, $event);

        $this->assertFalse($result);
    }

    public function testLicenseeParticipationToEventSetsDefaultActivityFromLicense(): void
    {
        $license = $this->createMock(License::class);
        $license->method('getActivities')->willReturn([LicenseActivityType::AC]);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')->willReturn($license);

        $event = $this->createMock(TrainingEvent::class);
        $event->method('getStartsAt')->willReturn(new \DateTimeImmutable('2025-01-15'));
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection());

        $this->eventParticipationRepository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->eventHelper->licenseeParticipationToEvent($licensee, $event);

        $this->assertSame(LicenseActivityType::AC, $result->getActivity());
    }

    public function testLicenseeParticipationToEventSetsRegisteredStateForTrainingEventWithGroupAccess(): void
    {
        $group = $this->createStub(Group::class);

        $license = $this->createMock(License::class);
        $license->method('getActivities')->willReturn([LicenseActivityType::AC]);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')->willReturn($license);
        $licensee->method('getGroups')->willReturn(new ArrayCollection([$group]));

        $event = $this->createMock(TrainingEvent::class);
        $event->method('getStartsAt')->willReturn(new \DateTimeImmutable('2025-01-15'));
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection([$group]));

        $this->eventParticipationRepository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->eventHelper->licenseeParticipationToEvent($licensee, $event);

        $this->assertSame(EventParticipationStateType::REGISTERED, $result->getParticipationState());
    }

    public function testLicenseeParticipationToEventDoesNotSetStateForContestEvent(): void
    {
        $license = $this->createMock(License::class);
        $license->method('getActivities')->willReturn([LicenseActivityType::AC]);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')->willReturn($license);

        $event = $this->createMock(ContestEvent::class);
        $event->method('getStartsAt')->willReturn(new \DateTimeImmutable('2025-01-15'));
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection());

        $this->eventParticipationRepository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->eventHelper->licenseeParticipationToEvent($licensee, $event);

        $this->assertNull($result->getParticipationState());
    }

    public function testLicenseeParticipationToEventDoesNotSetStateForHobbyContestEvent(): void
    {
        $license = $this->createMock(License::class);
        $license->method('getActivities')->willReturn([LicenseActivityType::AC]);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')->willReturn($license);

        $event = $this->createMock(HobbyContestEvent::class);
        $event->method('getStartsAt')->willReturn(new \DateTimeImmutable('2025-01-15'));
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection());

        $this->eventParticipationRepository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->eventHelper->licenseeParticipationToEvent($licensee, $event);

        $this->assertNull($result->getParticipationState());
    }

    public function testGetAllParticipantsForEventReturnsExistingParticipationsForContestEvent(): void
    {
        $participation1 = $this->createStub(EventParticipation::class);
        $participation2 = $this->createStub(EventParticipation::class);

        $event = $this->createMock(ContestEvent::class);
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection());
        $event->method('getParticipations')->willReturn(new ArrayCollection([$participation1, $participation2]));

        $result = $this->eventHelper->getAllParticipantsForEvent($event);

        $this->assertCount(2, $result);
        $this->assertSame($participation1, $result[0]);
        $this->assertSame($participation2, $result[1]);
    }

    public function testGetAllParticipantsForEventIncludesAllGroupMembersForTrainingEvent(): void
    {
        $licensee1 = $this->createMock(Licensee::class);
        $licensee1->method('getId')->willReturn(1);
        $licensee1->method('getLicenseForSeason')->willReturn(null);
        $licensee1->method('getGroups')->willReturn(new ArrayCollection());

        $licensee2 = $this->createMock(Licensee::class);
        $licensee2->method('getId')->willReturn(2);
        $licensee2->method('getLicenseForSeason')->willReturn(null);
        $licensee2->method('getGroups')->willReturn(new ArrayCollection());

        $group = $this->createMock(Group::class);
        $group->method('getLicensees')->willReturn(new ArrayCollection([$licensee1, $licensee2]));

        $event = $this->createMock(TrainingEvent::class);
        $event->method('getStartsAt')->willReturn(new \DateTimeImmutable('2025-01-15'));
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection([$group]));

        $this->eventParticipationRepository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->eventHelper->getAllParticipantsForEvent($event);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(EventParticipation::class, $result);
    }

    public function testGetAllParticipantsForEventDeduplicatesLicenseesFromMultipleGroups(): void
    {
        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getId')->willReturn(1);
        $licensee->method('getLicenseForSeason')->willReturn(null);
        $licensee->method('getGroups')->willReturn(new ArrayCollection());

        $group1 = $this->createMock(Group::class);
        $group1->method('getLicensees')->willReturn(new ArrayCollection([$licensee]));

        $group2 = $this->createMock(Group::class);
        $group2->method('getLicensees')->willReturn(new ArrayCollection([$licensee]));

        $event = $this->createMock(TrainingEvent::class);
        $event->method('getStartsAt')->willReturn(new \DateTimeImmutable('2025-01-15'));
        $event->method('getAssignedGroups')->willReturn(new ArrayCollection([$group1, $group2]));

        $this->eventParticipationRepository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->eventHelper->getAllParticipantsForEvent($event);

        // Should only have 1 participation even though licensee is in 2 groups
        $this->assertCount(1, $result);
        $this->assertInstanceOf(EventParticipation::class, $result[0]);
    }
}
