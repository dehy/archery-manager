<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service;

use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Service\LicenseeAccountMover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
final class LicenseeAccountMoverTest extends KernelTestCase
{
    private LicenseeAccountMover $mover;

    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->mover = self::getContainer()->get(LicenseeAccountMover::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testMoveToNewAccountKeepsOldAccountWhenItStillHasLicensees(): void
    {
        $licensee = $this->sharedAccountLicensee();
        $source = $licensee->getUser();
        $this->assertInstanceOf(\App\Entity\User::class, $source);
        $sourceId = $source->getId();
        $siblingCount = $source->getLicensees()->count() - 1;

        $user = $this->mover->moveToNewAccount($licensee, '  New.Adult@Example.org ', true);

        $this->entityManager->clear();
        $this->assertSame('new.adult@example.org', $user->getEmail());
        $this->assertFalse($user->isIsVerified());
        $reloadedSource = self::getContainer()->get(UserRepository::class)->find($sourceId);
        $this->assertInstanceOf(User::class, $reloadedSource, 'An account that still has licensees must never be deleted.');
        $this->assertCount($siblingCount, $reloadedSource->getLicensees());
        $this->assertSame('new.adult@example.org', self::getContainer()->get(LicenseeRepository::class)->find($licensee->getId())->getUser()->getEmail());
    }

    public function testEmptiedAccountIsDeletedOnlyWhenAsked(): void
    {
        $licensee = $this->soleLicenseeOfAccount();
        $sourceId = $licensee->getUser()->getId();
        $licenseeId = $licensee->getId();
        $target = $this->sharedAccountLicensee()->getUser();

        $this->mover->moveToExistingAccount($licensee, $target, true);

        $this->entityManager->clear();
        $this->assertNull(self::getContainer()->get(UserRepository::class)->find($sourceId));
        $moved = self::getContainer()->get(LicenseeRepository::class)->find($licenseeId);
        $this->assertInstanceOf(Licensee::class, $moved, 'Deleting the old account must not cascade to the moved licensee.');
        $this->assertInstanceOf(\App\Entity\User::class, $target);
        $this->assertSame($target->getId(), $moved->getUser()->getId());
    }

    public function testEmptiedAccountIsKeptWhenNotAsked(): void
    {
        $licensee = $this->soleLicenseeOfAccount();
        $sourceId = $licensee->getUser()->getId();
        $target = $this->sharedAccountLicensee()->getUser();

        $this->mover->moveToExistingAccount($licensee, $target, false);

        $this->entityManager->clear();
        $this->assertInstanceOf(User::class, self::getContainer()->get(UserRepository::class)->find($sourceId));
    }

    public function testMovingOntoTheSameAccountIsRejected(): void
    {
        $licensee = $this->sharedAccountLicensee();

        $this->expectException(\InvalidArgumentException::class);
        $this->mover->moveToExistingAccount($licensee, $licensee->getUser(), false);
    }

    private function sharedAccountLicensee(): Licensee
    {
        foreach (self::getContainer()->get(LicenseeRepository::class)->findAll() as $licensee) {
            if ($licensee->getUser()->getLicensees()->count() > 1) {
                return $licensee;
            }
        }

        $this->fail('No shared-account licensee in fixtures.');
    }

    private function soleLicenseeOfAccount(): Licensee
    {
        foreach (self::getContainer()->get(LicenseeRepository::class)->findAll() as $licensee) {
            if (1 === $licensee->getUser()->getLicensees()->count() && !\in_array('ROLE_CLUB_ADMIN', $licensee->getUser()->getRoles(), true)) {
                return $licensee;
            }
        }

        $this->fail('No single-licensee account in fixtures.');
    }
}
