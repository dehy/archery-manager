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
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

final class LicenseeAccessVoterTest extends TestCase
{
    private const int SEASON = 2027;

    private Club $club;

    private Club $otherClub;

    private LicenseeAccessVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->club = new Club();
        $this->otherClub = new Club();

        $seasonHelper = $this->createStub(SeasonHelper::class);
        $seasonHelper->method('getSelectedSeason')->willReturn(self::SEASON);

        $this->voter = new LicenseeAccessVoter($seasonHelper, new RoleHierarchy([
            'ROLE_CLUB_ADMIN' => ['ROLE_USER'],
            'ROLE_ADMIN' => ['ROLE_CLUB_ADMIN'],
        ]));
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

    private function licensee(?Club $club): Licensee
    {
        $licensee = new Licensee();
        if ($club instanceof Club) {
            $licensee->addLicense(new License()->setSeason(self::SEASON)->setClub($club));
        }

        return $licensee;
    }
}
