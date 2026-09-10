<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Club;
use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\ClubHelper;
use App\Security\Voter\LicenseeVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class LicenseeVoterTest extends TestCase
{
    private ClubHelper&MockObject $clubHelper;

    private LicenseeVoter $voter;

    protected function setUp(): void
    {
        $this->clubHelper = $this->createMock(ClubHelper::class);
        $this->voter = new LicenseeVoter($this->clubHelper);
    }

    public function testDoesNotSupportOtherAttributes(): void
    {
        $result = $this->voter->vote($this->createToken($this->createUser()), new Licensee(), ['view']);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportOtherSubjects(): void
    {
        $result = $this->voter->vote($this->createToken($this->createUser()), new \stdClass(), [LicenseeVoter::RENEW]);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDeniesAccessForNonUserToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, new Licensee(), [LicenseeVoter::RENEW]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanRenewAnyLicensee(): void
    {
        $user = $this->createUser(['ROLE_ADMIN']);
        $result = $this->voter->vote($this->createToken($user), new Licensee(), [LicenseeVoter::RENEW]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRegularUserCannotRenew(): void
    {
        $user = $this->createUser(['ROLE_USER']);
        $result = $this->voter->vote($this->createToken($user), new Licensee(), [LicenseeVoter::RENEW]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testClubAdminCanRenewLicenseeAffiliatedWithTheirClub(): void
    {
        $club = $this->createStub(Club::class);
        $this->clubHelper->method('getClubForUser')->willReturn($club);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('hasLicenseForClub')->with($club)->willReturn(true);

        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $result = $this->voter->vote($this->createToken($user), $licensee, [LicenseeVoter::RENEW]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testClubAdminCannotRenewLicenseeFromAnotherClub(): void
    {
        $club = $this->createStub(Club::class);
        $this->clubHelper->method('getClubForUser')->willReturn($club);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('hasLicenseForClub')->with($club)->willReturn(false);

        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $result = $this->voter->vote($this->createToken($user), $licensee, [LicenseeVoter::RENEW]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
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
}
