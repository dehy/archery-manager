<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Season;
use PHPUnit\Framework\TestCase;

final class SeasonTest extends TestCase
{
    public function testSeasonForDateInSeptember(): void
    {
        $date = new \DateTimeImmutable('2023-09-01');
        $season = Season::seasonForDate($date);
        $this->assertSame(2024, $season);
    }

    public function testSeasonForDateInOctober(): void
    {
        $date = new \DateTimeImmutable('2023-10-15');
        $season = Season::seasonForDate($date);
        $this->assertSame(2024, $season);
    }

    public function testSeasonForDateInDecember(): void
    {
        $date = new \DateTimeImmutable('2023-12-31');
        $season = Season::seasonForDate($date);
        $this->assertSame(2024, $season);
    }

    public function testSeasonForDateInJanuary(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');
        $season = Season::seasonForDate($date);
        $this->assertSame(2024, $season);
    }

    public function testSeasonForDateInAugust(): void
    {
        $date = new \DateTimeImmutable('2023-08-31');
        $season = Season::seasonForDate($date);
        $this->assertSame(2023, $season);
    }

    public function testSeasonForDateInMay(): void
    {
        $date = new \DateTimeImmutable('2023-05-15');
        $season = Season::seasonForDate($date);
        $this->assertSame(2023, $season);
    }

    public function testSeasonForDateBoundaryConditions(): void
    {
        // Test the exact boundary at September 1st
        $augustDate = new \DateTimeImmutable('2023-08-31 23:59:59');
        $septemberDate = new \DateTimeImmutable('2023-09-01 00:00:00');

        $this->assertSame(2023, Season::seasonForDate($augustDate));
        $this->assertSame(2024, Season::seasonForDate($septemberDate));
    }

    /**
     * @dataProvider seasonDataProvider
     */
    public function testSeasonForVariousDates(string $dateString, int $expectedSeason): void
    {
        $date = new \DateTimeImmutable($dateString);
        $this->assertSame($expectedSeason, Season::seasonForDate($date));
    }

    public static function seasonDataProvider(): \Iterator
    {
        yield ['2020-01-01', 2020];
        yield ['2020-08-31', 2020];
        yield ['2020-09-01', 2021];
        yield ['2020-12-31', 2021];
        yield ['2021-06-15', 2021];
        yield ['2021-09-15', 2022];
        yield ['2022-02-28', 2022];
        yield ['2022-09-30', 2023];
        yield ['2023-07-04', 2023];
        yield ['2023-11-11', 2024];
    }
}
