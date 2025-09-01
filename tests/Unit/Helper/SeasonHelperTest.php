<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Entity\Season;
use App\Helper\SeasonHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SeasonHelperTest extends TestCase
{
    private SessionInterface $session;

    private SeasonHelper $seasonHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getSession')
            ->willReturn($this->session);

        $this->seasonHelper = new SeasonHelper($requestStack);
    }

    public function testGetSelectedSeasonFromSession(): void
    {
        $expectedSeason = 2024;

        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('selectedSeason')
            ->willReturn($expectedSeason);

        $season = $this->seasonHelper->getSelectedSeason();
        $this->assertSame($expectedSeason, $season);
    }

    public function testGetSelectedSeasonFallsBackToCurrentSeason(): void
    {
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('selectedSeason')
            ->willReturn(null);

        // Mock the current date to ensure consistent test results
        $currentSeason = Season::seasonForDate(new \DateTimeImmutable());

        $season = $this->seasonHelper->getSelectedSeason();
        $this->assertSame($currentSeason, $season);
    }

    public function testSetSelectedSeason(): void
    {
        $expectedSeason = 2025;

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('selectedSeason', $expectedSeason);

        $this->seasonHelper->setSelectedSeason($expectedSeason);
    }

    public function testGetSelectedSeasonAfterSet(): void
    {
        $season = 2023;

        // First call to set
        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('selectedSeason', $season);

        // Second call to get
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('selectedSeason')
            ->willReturn($season);

        $this->seasonHelper->setSelectedSeason($season);
        $result = $this->seasonHelper->getSelectedSeason();

        $this->assertSame($season, $result);
    }
}
