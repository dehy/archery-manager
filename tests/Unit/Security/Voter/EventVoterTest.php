<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Club;
use App\Entity\Event;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Security\Voter\EventVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EventVoterTest extends TestCase
{
    private function createVoter(bool $isAdmin = false, bool $isClubAdmin = false): EventVoter
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static function (string $attribute) use ($isAdmin, $isClubAdmin): bool {
                return match ($attribute) {
                    'ROLE_ADMIN' => $isAdmin,
                    'ROLE_CLUB_ADMIN' => $isClubAdmin,
                    default => false,
                };
            },
        );

        return new EventVoter($security);
    }

    private function createUser(array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles($roles);

        return $user;
    }

    private function createToken(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    /**
     * Event ending in January 2026 → Season::seasonForDate returns 2026 (month < 9, year = 2026).
     */
    private function createEvent(?Club $club = null): Event
    {
        $event = $this->createMock(Event::class);
        $event->method('getEndsAt')->willReturn(new \DateTimeImmutable('2026-01-15'));
        $event->method('getClub')->willReturn($club);

        return $event;
    }

    private function addLicenseeWithClub(User $user, ?Club $club, int $season = 2026): void
    {
        $license = $this->createMock(License::class);
        $license->method('getClub')->willReturn($club);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')->with($season)->willReturn($club ? $license : null);

        $user->addLicensee($licensee);
    }

    // --- supports() tests ---

    public function testSupportsEditAttributeWithEvent(): void
    {
        $voter = $this->createVoter();
        $token = $this->createToken($this->createUser());
        $event = $this->createEvent();

        $result = $voter->vote($token, $event, [EventVoter::EDIT]);
        $this->assertContains($result, [VoterInterface::ACCESS_GRANTED, VoterInterface::ACCESS_DENIED]);
    }

    public function testSupportsDeleteAttributeWithEvent(): void
    {
        $voter = $this->createVoter();
        $token = $this->createToken($this->createUser());
        $event = $this->createEvent();

        $result = $voter->vote($token, $event, [EventVoter::DELETE]);
        $this->assertContains($result, [VoterInterface::ACCESS_GRANTED, VoterInterface::ACCESS_DENIED]);
    }

    public function testSupportsViewAttributeWithEvent(): void
    {
        $voter = $this->createVoter();
        $token = $this->createToken($this->createUser());
        $event = $this->createEvent();

        $result = $voter->vote($token, $event, [EventVoter::VIEW]);
        $this->assertContains($result, [VoterInterface::ACCESS_GRANTED, VoterInterface::ACCESS_DENIED]);
    }

    public function testDoesNotSupportUnknownAttribute(): void
    {
        $voter = $this->createVoter();
        $token = $this->createToken($this->createUser());
        $event = $this->createEvent();

        $result = $voter->vote($token, $event, ['UNSUPPORTED_ATTRIBUTE']);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportNonEventSubject(): void
    {
        $voter = $this->createVoter();
        $token = $this->createToken($this->createUser());

        $result = $voter->vote($token, new \stdClass(), [EventVoter::EDIT]);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    // --- Anonymous user ---

    public function testAnonymousUserIsDenied(): void
    {
        $voter = $this->createVoter();
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $event = $this->createEvent();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $event, [EventVoter::EDIT]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $event, [EventVoter::DELETE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $event, [EventVoter::VIEW]));
    }

    // --- Admin user ---

    public function testAdminCanEditAnyEvent(): void
    {
        $voter = $this->createVoter(isAdmin: true);
        $user = $this->createUser(['ROLE_ADMIN']);
        $token = $this->createToken($user);
        $event = $this->createEvent();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $event, [EventVoter::EDIT]));
    }

    public function testAdminCanDeleteAnyEvent(): void
    {
        $voter = $this->createVoter(isAdmin: true);
        $user = $this->createUser(['ROLE_ADMIN']);
        $token = $this->createToken($user);
        $event = $this->createEvent();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $event, [EventVoter::DELETE]));
    }

    // --- Regular user ---

    public function testRegularUserCannotEditEvent(): void
    {
        $club = $this->createStub(Club::class);
        $voter = $this->createVoter();
        $user = $this->createUser(['ROLE_USER']);
        $this->addLicenseeWithClub($user, $club);
        $token = $this->createToken($user);
        $event = $this->createEvent($club);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $event, [EventVoter::EDIT]));
    }

    public function testRegularUserCannotDeleteEvent(): void
    {
        $club = $this->createStub(Club::class);
        $voter = $this->createVoter();
        $user = $this->createUser(['ROLE_USER']);
        $this->addLicenseeWithClub($user, $club);
        $token = $this->createToken($user);
        $event = $this->createEvent($club);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $event, [EventVoter::DELETE]));
    }

    public function testRegularUserInSameClubCanViewEvent(): void
    {
        $club = $this->createStub(Club::class);
        $voter = $this->createVoter();
        $user = $this->createUser(['ROLE_USER']);
        $this->addLicenseeWithClub($user, $club);
        $token = $this->createToken($user);
        $event = $this->createEvent($club);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $event, [EventVoter::VIEW]));
    }

    public function testRegularUserInDifferentClubCannotViewEvent(): void
    {
        $userClub = $this->createStub(Club::class);
        $eventClub = $this->createStub(Club::class);
        $voter = $this->createVoter();
        $user = $this->createUser(['ROLE_USER']);
        $this->addLicenseeWithClub($user, $userClub);
        $token = $this->createToken($user);
        $event = $this->createEvent($eventClub);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $event, [EventVoter::VIEW]));
    }

    // --- Club admin ---

    public function testClubAdminCanEditEventInOwnClub(): void
    {
        $club = $this->createStub(Club::class);
        $voter = $this->createVoter(isClubAdmin: true);
        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $this->addLicenseeWithClub($user, $club);
        $token = $this->createToken($user);
        $event = $this->createEvent($club);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $event, [EventVoter::EDIT]));
    }

    public function testClubAdminCanDeleteEventInOwnClub(): void
    {
        $club = $this->createStub(Club::class);
        $voter = $this->createVoter(isClubAdmin: true);
        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $this->addLicenseeWithClub($user, $club);
        $token = $this->createToken($user);
        $event = $this->createEvent($club);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $event, [EventVoter::DELETE]));
    }

    public function testClubAdminCannotEditEventInDifferentClub(): void
    {
        $adminClub = $this->createStub(Club::class);
        $eventClub = $this->createStub(Club::class);
        $voter = $this->createVoter(isClubAdmin: true);
        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $this->addLicenseeWithClub($user, $adminClub);
        $token = $this->createToken($user);
        $event = $this->createEvent($eventClub);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $event, [EventVoter::EDIT]));
    }

    public function testClubAdminCannotDeleteEventInDifferentClub(): void
    {
        $adminClub = $this->createStub(Club::class);
        $eventClub = $this->createStub(Club::class);
        $voter = $this->createVoter(isClubAdmin: true);
        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $this->addLicenseeWithClub($user, $adminClub);
        $token = $this->createToken($user);
        $event = $this->createEvent($eventClub);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $event, [EventVoter::DELETE]));
    }

    public function testClubAdminWithNoLicenseCannotEditEvent(): void
    {
        $club = $this->createStub(Club::class);
        $voter = $this->createVoter(isClubAdmin: true);
        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $this->addLicenseeWithClub($user, null); // no club via license
        $token = $this->createToken($user);
        $event = $this->createEvent($club);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $event, [EventVoter::EDIT]));
    }
}
