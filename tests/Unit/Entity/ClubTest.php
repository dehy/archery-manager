<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Club;
use App\Entity\License;
use PHPUnit\Framework\TestCase;

final class ClubTest extends TestCase
{
    public function testConstructorInitializesCollectionsAndTimestamps(): void
    {
        $club = new Club();

        $this->assertCount(0, $club->getEvents());
        $this->assertCount(0, $club->getGroups());
        $this->assertCount(0, $club->getLicenses());
        $this->assertInstanceOf(\DateTimeImmutable::class, $club->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $club->getUpdatedAt());
        $this->assertEquals($club->getCreatedAt(), $club->getUpdatedAt());
    }

    public function testToString(): void
    {
        $club = new Club();
        $club->setName('Les Archers');
        $club->setCity('Bordeaux');

        $this->assertSame('Bordeaux - Les Archers', (string) $club);
    }

    public function testSetAndGetName(): void
    {
        $club = new Club();
        $club->setName('Test Club');

        $this->assertSame('Test Club', $club->getName());
    }

    public function testSetAndGetCity(): void
    {
        $club = new Club();
        $club->setCity('Paris');

        $this->assertSame('Paris', $club->getCity());
    }

    public function testSetAndGetLogoName(): void
    {
        $club = new Club();
        $club->setLogoName('logo.png');

        $this->assertSame('logo.png', $club->getLogoName());
    }

    public function testSetAndGetPrimaryColor(): void
    {
        $club = new Club();
        $club->setPrimaryColor('#FF5733');

        $this->assertSame('#FF5733', $club->getPrimaryColor());
    }

    public function testSetAndGetContactEmail(): void
    {
        $club = new Club();
        $club->setContactEmail('contact@club.com');

        $this->assertSame('contact@club.com', $club->getContactEmail());
    }

    public function testSetAndGetFftaCode(): void
    {
        $club = new Club();
        $club->setFftaCode('12345678');

        $this->assertSame('12345678', $club->getFftaCode());
    }

    public function testSetAndGetFftaUsername(): void
    {
        $club = new Club();
        $club->setFftaUsername('ffta_user');

        $this->assertSame('ffta_user', $club->getFftaUsername());
    }

    public function testSetAndGetFftaPassword(): void
    {
        $club = new Club();
        $club->setFftaPassword('secret_password');

        $this->assertSame('secret_password', $club->getFftaPassword());
    }

    public function testAddAndRemoveLicense(): void
    {
        $club = new Club();

        $club->addLicense($this->createMock(License::class));
        $this->assertCount(1, $club->getLicenses());
        $this->assertTrue($club->getLicenses()->contains($this->createMock(License::class)));

        $club->removeLicense($this->createMock(License::class));
        $this->assertCount(0, $club->getLicenses());
        $this->assertFalse($club->getLicenses()->contains($this->createMock(License::class)));
    }

    public function testAddLicenseDoesNotDuplicateLicenses(): void
    {
        $club = new Club();

        $club->addLicense($this->createMock(License::class));
        $club->addLicense($this->createMock(License::class));

        $this->assertCount(1, $club->getLicenses());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $club = new Club();
        $date = new \DateTimeImmutable('2025-01-01 12:00:00');

        $club->setUpdatedAt($date);

        $this->assertSame($date, $club->getUpdatedAt());
    }

    public function testFluentInterface(): void
    {
        $club = new Club();

        $result = $club
            ->setName('Test Club')
            ->setCity('Test City')
            ->setPrimaryColor('#000000')
            ->setContactEmail('test@club.com')
            ->setFftaCode('12345678');

        $this->assertSame($club, $result);
    }
}
