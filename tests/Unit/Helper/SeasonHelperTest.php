<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Entity\Season;
use App\Helper\SeasonHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class SeasonHelperTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $session;

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

    // ── Stateless mobile API: the season comes from the X-Season header, never from the session ──

    #[DataProvider('validSeasonHeaders')]
    public function testApiRequestUsesTheSeasonHeader(string $header, int $expected): void
    {
        $this->assertSame($expected, $this->apiSeasonHelper(['HTTP_X_SEASON' => $header])->getSelectedSeason());
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function validSeasonHeaders(): iterable
    {
        yield 'a season' => ['2025', 2025];
        yield 'lower bound' => ['2000', 2000];
        yield 'upper bound' => ['2100', 2100];
    }

    public function testApiRequestWithoutHeaderDefaultsToTheCurrentSeason(): void
    {
        $this->assertSame(Season::seasonForDate(new \DateTimeImmutable()), $this->apiSeasonHelper([])->getSelectedSeason());
    }

    #[DataProvider('invalidSeasonHeaders')]
    public function testApiRequestWithAnInvalidSeasonHeaderIsABadRequest(string $header): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->apiSeasonHelper(['HTTP_X_SEASON' => $header])->getSelectedSeason();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSeasonHeaders(): iterable
    {
        yield 'not a number' => ['abc'];
        yield 'below range' => ['1999'];
        yield 'above range' => ['2101'];
        yield 'float' => ['2025.5'];
        yield 'scientific notation' => ['1e3'];
    }

    /**
     * @param array<string, string> $server
     */
    private function apiSeasonHelper(array $server): SeasonHelper
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->never())->method('getSession');
        $requestStack->method('getCurrentRequest')->willReturn(Request::create('/api/v1/me', server: $server));

        return new SeasonHelper($requestStack);
    }
}
