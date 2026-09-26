<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Licensee;
use App\Repository\LicenseeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LicenseeRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private LicenseeRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(LicenseeRepository::class);
    }

    public function testFindByCodesWithLicensesReturnsRequestedLicensees(): void
    {
        $licensees = $this->repository->findAll();
        $this->assertGreaterThanOrEqual(2, \count($licensees));
        $codes = [
            (string) $licensees[0]->getFftaMemberCode(),
            (string) $licensees[1]->getFftaMemberCode(),
        ];

        $result = $this->repository->findByCodesWithLicenses([$codes[0], $codes[1], $codes[0]]);

        $this->assertEqualsCanonicalizing(
            $codes,
            array_map(static fn (Licensee $licensee): string => (string) $licensee->getFftaMemberCode(), $result),
        );
    }

    public function testFindByCodesWithLicensesReturnsEmptyArrayForEmptyCodes(): void
    {
        $this->assertSame([], $this->repository->findByCodesWithLicenses([]));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
