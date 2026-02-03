<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\DBAL\Types\GenderType;
use App\Entity\Arrow;
use App\Entity\Bow;
use App\Entity\Group;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class LicenseeTest extends TestCase
{
    public function testLicenseeCanBeCreated(): void
    {
        $licensee = new Licensee();

        $this->assertNull($licensee->getId());
        $this->assertCount(0, $licensee->getLicenses());
        $this->assertCount(0, $licensee->getBows());
        $this->assertCount(0, $licensee->getArrows());
        $this->assertCount(0, $licensee->getGroups());
        $this->assertInstanceOf(\DateTimeImmutable::class, $licensee->getUpdatedAt());
    }

    public function testSetAndGetUser(): void
    {
        $licensee = new Licensee();
        $user = $this->createMock(User::class);

        $user->expects($this->once())
            ->method('addLicensee')
            ->with($licensee);

        $licensee->setUser($user);

        $this->assertSame($user, $licensee->getUser());
    }

    public function testSetAndGetGender(): void
    {
        $licensee = new Licensee();
        $licensee->setGender(GenderType::FEMALE);

        $this->assertSame(GenderType::FEMALE, $licensee->getGender());
    }

    public function testSetAndGetFirstname(): void
    {
        $licensee = new Licensee();
        $licensee->setFirstname('Alice');

        $this->assertSame('Alice', $licensee->getFirstname());
    }

    public function testSetAndGetLastname(): void
    {
        $licensee = new Licensee();
        $licensee->setLastname('Archer');

        $this->assertSame('Archer', $licensee->getLastname());
    }

    public function testGetFullname(): void
    {
        $licensee = new Licensee();
        $licensee->setFirstname('Bob')
            ->setLastname('Builder');

        $this->assertSame('Bob Builder', $licensee->getFullname());
    }

    public function testGetFirstnameWithInitial(): void
    {
        $licensee = new Licensee();
        $licensee->setFirstname('Charlie')
            ->setLastname('Brown');

        $this->assertSame('Charlie B.', $licensee->getFirstnameWithInitial());
    }

    public function testToStringReturnsFullname(): void
    {
        $licensee = new Licensee();
        $licensee->setFirstname('Diana')
            ->setLastname('Prince');

        $this->assertSame('Diana Prince', (string) $licensee);
    }

    public function testSetAndGetBirthdate(): void
    {
        $licensee = new Licensee();
        $birthdate = new \DateTime('1990-05-15');

        $licensee->setBirthdate($birthdate);

        $this->assertSame($birthdate, $licensee->getBirthdate());
    }

    public function testGetAge(): void
    {
        $licensee = new Licensee();
        $birthdate = new \DateTime('-25 years');

        $licensee->setBirthdate($birthdate);

        $this->assertSame(25, $licensee->getAge());
    }

    public function testSetAndGetFftaId(): void
    {
        $licensee = new Licensee();
        $licensee->setFftaId(123456);

        $this->assertSame(123456, $licensee->getFftaId());
    }

    public function testSetAndGetFftaMemberCode(): void
    {
        $licensee = new Licensee();
        $licensee->setFftaMemberCode('12345678');

        $this->assertSame('12345678', $licensee->getFftaMemberCode());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $licensee = new Licensee();
        $updatedAt = new \DateTimeImmutable('2025-01-15');

        $licensee->setUpdatedAt($updatedAt);

        $this->assertSame($updatedAt, $licensee->getUpdatedAt());
    }

    public function testAddLicense(): void
    {
        $licensee = new Licensee();
        $license = $this->createMock(License::class);

        $license->expects($this->once())
            ->method('setLicensee')
            ->with($licensee);

        $licensee->addLicense($license);

        $this->assertCount(1, $licensee->getLicenses());
        $this->assertTrue($licensee->getLicenses()->contains($license));
    }

    public function testAddLicenseDoesNotAddDuplicate(): void
    {
        $licensee = new Licensee();
        $license = $this->createMock(License::class);

        $license->method('setLicensee');

        $licensee->addLicense($license);
        $licensee->addLicense($license);

        $this->assertCount(1, $licensee->getLicenses());
    }

    public function testRemoveLicense(): void
    {
        $licensee = new Licensee();
        $license = $this->createMock(License::class);

        $license->method('setLicensee');
        $license->method('getLicensee')->willReturn($licensee);

        $licensee->addLicense($license);
        $this->assertCount(1, $licensee->getLicenses());

        $license->expects($this->once())
            ->method('setLicensee')
            ->with(null);

        $licensee->removeLicense($license);

        $this->assertCount(0, $licensee->getLicenses());
    }

    public function testGetLicenseForSeason(): void
    {
        $licensee = new Licensee();
        $license2024 = $this->createMock(License::class);
        $license2025 = $this->createMock(License::class);

        $license2024->method('setLicensee');
        $license2024->method('getSeason')->willReturn(2024);

        $license2025->method('setLicensee');
        $license2025->method('getSeason')->willReturn(2025);

        $licensee->addLicense($license2024);
        $licensee->addLicense($license2025);

        $this->assertSame($license2024, $licensee->getLicenseForSeason(2024));
        $this->assertSame($license2025, $licensee->getLicenseForSeason(2025));
        $this->assertNull($licensee->getLicenseForSeason(2026));
    }

    public function testGetLicenseForSeasonThrowsExceptionWhenMultipleLicensesForSameSeason(): void
    {
        $licensee = new Licensee();
        $license1 = $this->createMock(License::class);
        $license2 = $this->createMock(License::class);

        $license1->method('setLicensee');
        $license1->method('getSeason')->willReturn(2025);

        $license2->method('setLicensee');
        $license2->method('getSeason')->willReturn(2025);

        $licensee->addLicense($license1);
        $licensee->addLicense($license2);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Licensee should not have multiple licenses for same season');

        $licensee->getLicenseForSeason(2025);
    }

    public function testAddBow(): void
    {
        $licensee = new Licensee();
        $bow = $this->createMock(Bow::class);

        $bow->expects($this->once())
            ->method('setOwner')
            ->with($licensee);

        $licensee->addBow($bow);

        $this->assertCount(1, $licensee->getBows());
        $this->assertTrue($licensee->getBows()->contains($bow));
    }

    public function testRemoveBow(): void
    {
        $licensee = new Licensee();
        $bow = $this->createMock(Bow::class);

        $bow->method('setOwner');
        $bow->method('getOwner')->willReturn($licensee);

        $licensee->addBow($bow);

        $bow->expects($this->once())
            ->method('setOwner')
            ->with(null);

        $licensee->removeBow($bow);

        $this->assertCount(0, $licensee->getBows());
    }

    public function testAddArrow(): void
    {
        $licensee = new Licensee();
        $arrow = $this->createMock(Arrow::class);

        $arrow->expects($this->once())
            ->method('setOwner')
            ->with($licensee);

        $licensee->addArrow($arrow);

        $this->assertCount(1, $licensee->getArrows());
        $this->assertTrue($licensee->getArrows()->contains($arrow));
    }

    public function testRemoveArrow(): void
    {
        $licensee = new Licensee();
        $arrow = $this->createMock(Arrow::class);

        $arrow->method('setOwner');
        $arrow->method('getOwner')->willReturn($licensee);

        $licensee->addArrow($arrow);

        $arrow->expects($this->once())
            ->method('setOwner')
            ->with(null);

        $licensee->removeArrow($arrow);

        $this->assertCount(0, $licensee->getArrows());
    }

    public function testAddGroup(): void
    {
        $licensee = new Licensee();
        $group = $this->createMock(Group::class);

        $group->expects($this->once())
            ->method('addLicensee')
            ->with($licensee);

        $licensee->addGroup($group);

        $this->assertCount(1, $licensee->getGroups());
        $this->assertTrue($licensee->getGroups()->contains($group));
    }

    public function testRemoveGroup(): void
    {
        $licensee = new Licensee();
        $group = $this->createMock(Group::class);

        $group->method('addLicensee');

        $licensee->addGroup($group);

        $group->expects($this->once())
            ->method('removeLicensee')
            ->with($licensee);

        $licensee->removeGroup($group);

        $this->assertCount(0, $licensee->getGroups());
    }

    public function testFluentInterface(): void
    {
        $licensee = new Licensee();
        $user = $this->createMock(User::class);
        $user->method('addLicensee');

        $result = $licensee
            ->setUser($user)
            ->setGender(GenderType::MALE)
            ->setFirstname('Test')
            ->setLastname('User')
            ->setBirthdate(new \DateTime('1990-01-01'))
            ->setFftaId(123)
            ->setFftaMemberCode('12345678')
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->assertSame($licensee, $result);
    }
}
