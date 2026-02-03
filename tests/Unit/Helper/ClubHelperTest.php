<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Entity\Club;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Exception\NoActiveClubException;
use App\Helper\ClubHelper;
use App\Helper\LicenseHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ClubHelperTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $licenseHelper;

    private ClubHelper $clubHelper;

    protected function setUp(): void
    {
        $this->licenseHelper = $this->createMock(LicenseHelper::class);
        $this->clubHelper = new ClubHelper($this->licenseHelper);
    }

    public function testActiveClubReturnsClubFromCurrentLicense(): void
    {
        $club = $this->createMock(Club::class);
        $license = $this->createMock(License::class);
        $license->method('getClub')->willReturn($club);

        $this->licenseHelper
            ->expects($this->once())
            ->method('getCurrentLicenseeCurrentLicense')
            ->willReturn($license);

        $result = $this->clubHelper->activeClub();

        $this->assertSame($club, $result);
    }

    public function testActiveClubReturnsNullWhenNoCurrentLicense(): void
    {
        $this->licenseHelper
            ->expects($this->once())
            ->method('getCurrentLicenseeCurrentLicense')
            ->willReturn(null);

        $result = $this->clubHelper->activeClub();

        $this->assertNotInstanceOf(Club::class, $result);
    }

    public function testGetClubForUserReturnsNullWhenUserIsNull(): void
    {
        $result = $this->clubHelper->getClubForUser(null);

        $this->assertNotInstanceOf(Club::class, $result);
    }

    public function testGetClubForUserReturnsClubFromUserLicensee(): void
    {
        $club = $this->createMock(Club::class);
        $license = $this->createMock(License::class);
        $license->method('getClub')->willReturn($club);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')
            ->willReturn($license);

        $user = $this->createMock(User::class);
        $user->method('getLicensees')
            ->willReturn(new ArrayCollection([$licensee]));

        $result = $this->clubHelper->getClubForUser($user);

        $this->assertSame($club, $result);
    }

    public function testGetClubForUserReturnsNullWhenNoLicenseForCurrentSeason(): void
    {
        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')
            ->willReturn(null);

        $user = $this->createMock(User::class);
        $user->method('getLicensees')
            ->willReturn(new ArrayCollection([$licensee]));

        $result = $this->clubHelper->getClubForUser($user);

        $this->assertNotInstanceOf(Club::class, $result);
    }

    public function testGetClubForUserReturnsNullWhenUserHasNoLicensees(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getLicensees')
            ->willReturn(new ArrayCollection([]));

        $result = $this->clubHelper->getClubForUser($user);

        $this->assertNotInstanceOf(Club::class, $result);
    }

    public function testActiveClubForReturnsClubForLicensee(): void
    {
        $club = $this->createMock(Club::class);
        $license = $this->createMock(License::class);
        $license->method('getClub')->willReturn($club);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')
            ->willReturn($license);

        $result = $this->clubHelper->activeClubFor($licensee);

        $this->assertSame($club, $result);
    }

    public function testActiveClubForThrowsExceptionWhenNoActiveClub(): void
    {
        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getLicenseForSeason')
            ->willReturn(null);

        $this->expectException(NoActiveClubException::class);

        $this->clubHelper->activeClubFor($licensee);
    }

    public function testPrimaryColorReturnsClubPrimaryColor(): void
    {
        $club = $this->createMock(Club::class);
        $club->method('getPrimaryColor')->willReturn('#FF5733');

        $license = $this->createMock(License::class);
        $license->method('getClub')->willReturn($club);

        $this->licenseHelper
            ->expects($this->once())
            ->method('getCurrentLicenseeCurrentLicense')
            ->willReturn($license);

        $result = $this->clubHelper->primaryColor();

        $this->assertSame('#FF5733', $result);
    }

    public function testPrimaryColorReturnsDefaultWhenNoActiveClub(): void
    {
        $this->licenseHelper
            ->expects($this->once())
            ->method('getCurrentLicenseeCurrentLicense')
            ->willReturn(null);

        $result = $this->clubHelper->primaryColor();

        $this->assertSame('#999999', $result);
    }

    public function testDefaultColorReturnsGray(): void
    {
        $result = $this->clubHelper->defaultColor();

        $this->assertSame('#999999', $result);
    }
}
