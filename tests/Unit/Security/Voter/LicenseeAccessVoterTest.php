<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Club;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\SeasonHelper;
use App\Security\Voter\LicenseeAccessVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

final class LicenseeAccessVoterTest extends TestCase
{
    /** The current season, given the mocked clock below (the season rolls over on 1 September). */
    private const int SEASON = 2027;

    private const int PAST_SEASON = 2026;

    private Club $club;

    private Club $otherClub;

    private LicenseeAccessVoter $voter;

    /** The season the request selects with X-Season. */
    private int $selectedSeason = self::SEASON;

    #[\Override]
    protected function setUp(): void
    {
        $this->club = new Club();
        $this->otherClub = new Club();

        $seasonHelper = $this->createStub(SeasonHelper::class);
        $seasonHelper->method('getSelectedSeason')->willReturnCallback(fn (): int => $this->selectedSeason);

        $this->voter = new LicenseeAccessVoter(
            $seasonHelper,
            new RoleHierarchy([
                'ROLE_CLUB_ADMIN' => ['ROLE_USER'],
                'ROLE_ADMIN' => ['ROLE_CLUB_ADMIN'],
            ]),
            new MockClock('2026-10-06 12:00:00'),
        );
    }

    /**
     * @return iterable<string, array{string, list<string>, string, bool}>
     */
    public static function decisions(): iterable
    {
        // [viewer's club, viewer's roles, attribute, expected]
        yield 'a member, someone of the same club: profile' => ['same', ['ROLE_USER'], LicenseeAccessVoter::VIEW, false];
        yield 'a member, someone of the same club: picture' => ['same', ['ROLE_USER'], LicenseeAccessVoter::VIEW_PICTURE, true];
        yield 'a coach, the same club: profile' => ['same', ['ROLE_COACH', 'ROLE_USER'], LicenseeAccessVoter::VIEW, true];
        yield 'a club admin, the same club: profile' => ['same', ['ROLE_CLUB_ADMIN'], LicenseeAccessVoter::VIEW, true];
        yield 'a coach, another club: profile' => ['other', ['ROLE_COACH', 'ROLE_USER'], LicenseeAccessVoter::VIEW, false];
        yield 'a club admin, another club: profile' => ['other', ['ROLE_CLUB_ADMIN'], LicenseeAccessVoter::VIEW, false];
        yield 'a member, another club: picture' => ['other', ['ROLE_USER'], LicenseeAccessVoter::VIEW_PICTURE, false];
        yield 'an admin, another club: profile' => ['other', ['ROLE_ADMIN'], LicenseeAccessVoter::VIEW, true];
        yield 'an admin, another club: picture' => ['other', ['ROLE_ADMIN'], LicenseeAccessVoter::VIEW_PICTURE, true];
        yield 'someone without license: profile' => ['none', ['ROLE_COACH'], LicenseeAccessVoter::VIEW, false];
        yield 'someone without license: picture' => ['none', ['ROLE_USER'], LicenseeAccessVoter::VIEW_PICTURE, false];
    }

    /**
     * @param list<string> $roles
     */
    #[DataProvider('decisions')]
    public function testDecisions(string $viewerClub, array $roles, string $attribute, bool $granted): void
    {
        $target = $this->licensee($this->club);
        $viewer = $this->user($roles, match ($viewerClub) {
            'same' => $this->licensee($this->club),
            'other' => $this->licensee($this->otherClub),
            default => $this->licensee(null),
        });

        $this->assertSame($granted ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED, $this->vote($viewer, $attribute, $target));
    }

    public function testTheOwnerSeesEverythingEvenWithoutALicenseForTheSeason(): void
    {
        $own = $this->licensee(null);
        $viewer = $this->user(['ROLE_USER'], $own);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($viewer, LicenseeAccessVoter::VIEW, $own));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($viewer, LicenseeAccessVoter::VIEW_PICTURE, $own));
    }

    public function testAUserWithSeveralLicenseesSeesAnyOfThem(): void
    {
        $first = $this->licensee($this->club);
        $second = $this->licensee($this->otherClub);
        $viewer = $this->user(['ROLE_USER'], $first);
        $viewer->addLicensee($second);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($viewer, LicenseeAccessVoter::VIEW, $second));
    }

    public function testOnlyTheLicensesOfTheViewerOrTheTargetInTheRelevantSeasonCount(): void
    {
        // Both held a license in this club last season; this season neither does.
        $coach = $this->user(['ROLE_COACH'], $this->licensee($this->club, self::PAST_SEASON));
        $target = $this->licensee($this->club, self::PAST_SEASON);
        $this->selectedSeason = self::PAST_SEASON;

        // The past roster is visible to those who were in it (directory, pictures)...
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($coach, LicenseeAccessVoter::VIEW_PICTURE, $target));
        // ...but profiles and medical certificates need the relation now, whatever season is selected.
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($coach, LicenseeAccessVoter::VIEW, $target));
    }

    public function testAPersonWhoLeftTheClubKeepsNoProfileAccessThroughAnOldSeason(): void
    {
        $coach = $this->user(['ROLE_COACH'], $this->licensee($this->club, self::PAST_SEASON, self::SEASON));
        $leftTheClub = $this->licensee($this->club, self::PAST_SEASON);
        $leftTheClub->addLicense(new License()->setSeason(self::SEASON)->setClub($this->otherClub));

        $this->selectedSeason = self::PAST_SEASON;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($coach, LicenseeAccessVoter::VIEW, $leftTheClub));
    }

    public function testASelectedSeasonWithoutLicenseGivesNoPictureAccess(): void
    {
        $member = $this->user(['ROLE_USER'], $this->licensee($this->club));
        $target = $this->licensee($this->club);
        $this->selectedSeason = 2019;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->vote($member, LicenseeAccessVoter::VIEW_PICTURE, $target));
    }

    public function testASeveralLicenseesViewerOnlyNeedsOneOfThemInTheClub(): void
    {
        $viewer = $this->user(['ROLE_COACH'], $this->licensee($this->otherClub));
        $viewer->addLicensee($this->licensee($this->club));

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($viewer, LicenseeAccessVoter::VIEW, $this->licensee($this->club)));
    }

    public function testItAbstainsForWhatItDoesNotHandle(): void
    {
        $viewer = $this->user(['ROLE_ADMIN'], $this->licensee($this->club));

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote($viewer, 'SOMETHING_ELSE', $this->licensee($this->club)));
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote($viewer, LicenseeAccessVoter::VIEW, new \stdClass()));
    }

    public function testAnAnonymousTokenIsDenied(): void
    {
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote(new NullToken(), $this->licensee($this->club), [LicenseeAccessVoter::VIEW_PICTURE]),
        );
    }

    private function vote(User $viewer, string $attribute, object $subject): int
    {
        return $this->voter->vote(new UsernamePasswordToken($viewer, 'api', $viewer->getRoles()), $subject, [$attribute]);
    }

    /**
     * @param list<string> $roles
     */
    private function user(array $roles, Licensee $licensee): User
    {
        $user = new User()->setRoles($roles);
        $user->addLicensee($licensee);

        return $user;
    }

    /**
     * A licensee holding a license of $club for each of $seasons (the current season by default).
     */
    private function licensee(?Club $club, int ...$seasons): Licensee
    {
        $licensee = new Licensee();
        if ($club instanceof Club) {
            foreach ([] === $seasons ? [self::SEASON] : $seasons as $season) {
                $licensee->addLicense(new License()->setSeason($season)->setClub($club));
            }
        }

        return $licensee;
    }
}
