<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\License;
use App\Entity\LicenseApplication;
use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\SeasonHelper;
use App\Security\Voter\LicenseApplicationVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class LicenseApplicationVoterTest extends TestCase
{
    private LicenseApplicationVoter $voter;

    protected function setUp(): void
    {
        $seasonHelper = $this->createMock(SeasonHelper::class);
        $seasonHelper->method('getSelectedSeason')->willReturn(2026);

        $this->voter = new LicenseApplicationVoter($seasonHelper);
    }

    public function testSupportsManageAttributeWithLicenseApplication(): void
    {
        $application = new LicenseApplication();
        $token = $this->createToken($this->createUser());

        $result = $this->voter->vote($token, $application, ['manage']);
        $this->assertContains($result, [VoterInterface::ACCESS_GRANTED, VoterInterface::ACCESS_DENIED]);
    }

    public function testDoesNotSupportOtherAttributes(): void
    {
        $application = new LicenseApplication();
        $token = $this->createToken($this->createUser());

        $result = $this->voter->vote($token, $application, ['view']);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportOtherSubjects(): void
    {
        $token = $this->createToken($this->createUser());

        $result = $this->voter->vote($token, new \stdClass(), ['manage']);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDeniesAccessForNonUserToken(): void
    {
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $application = new LicenseApplication();
        $result = $this->voter->vote($token, $application, ['manage']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanManageAnyApplication(): void
    {
        $user = $this->createUser(['ROLE_ADMIN']);
        $token = $this->createToken($user);

        $application = new LicenseApplication();
        $result = $this->voter->vote($token, $application, ['manage']);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRegularUserCannotManage(): void
    {
        $user = $this->createUser(['ROLE_USER']);
        $token = $this->createToken($user);

        $application = new LicenseApplication();
        $result = $this->voter->vote($token, $application, ['manage']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testClubAdminCanManageApplicationForSameClub(): void
    {
        $club = $this->createMock(\App\Entity\Club::class);

        // Application for the club
        $application = new LicenseApplication();
        $application->setClub($club);

        // User with ROLE_CLUB_ADMIN who has a license in the same club
        $license = $this->createMock(License::class);
        $license->method('getClub')->willReturn($club);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')->with(2026)->willReturn($license);

        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $user->addLicensee($licensee);

        $token = $this->createToken($user);
        $result = $this->voter->vote($token, $application, ['manage']);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testClubAdminCannotManageApplicationForDifferentClub(): void
    {
        $application = new LicenseApplication();
        $application->setClub($this->createMock(\App\Entity\Club::class));

        $license = $this->createMock(License::class);
        $license->method('getClub')->willReturn($this->createMock(\App\Entity\Club::class));

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')->with(2026)->willReturn($license);

        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $user->addLicensee($licensee);

        $token = $this->createToken($user);
        $result = $this->voter->vote($token, $application, ['manage']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testClubAdminWithNoLicenseCannotManage(): void
    {
        $application = new LicenseApplication();
        $application->setClub($this->createMock(\App\Entity\Club::class));

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')->with(2026)->willReturn(null);

        $user = $this->createUser(['ROLE_CLUB_ADMIN']);
        $user->addLicensee($licensee);

        $token = $this->createToken($user);
        $result = $this->voter->vote($token, $application, ['manage']);
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
