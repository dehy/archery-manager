<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Helper\ResultHelper;
use PHPUnit\Framework\TestCase;

final class ResultHelperTest extends TestCase
{
    public function testColorRatioWithMinimumRatio(): void
    {
        $color = ResultHelper::colorRatio(0.0);
        $this->assertSame(strtolower(ResultHelper::COLOR_LOWEST), $color);
    }

    public function testColorRatioWithMaximumRatio(): void
    {
        $color = ResultHelper::colorRatio(1.0);
        $this->assertSame(strtolower(ResultHelper::COLOR_BEST), $color);
    }

    public function testColorRatioWithMiddleRatio(): void
    {
        $color = ResultHelper::colorRatio(0.5);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);
        $this->assertNotSame(ResultHelper::COLOR_LOWEST, $color);
        $this->assertNotSame(ResultHelper::COLOR_BEST, $color);
    }

    public function testColorRatioWithQuarterRatio(): void
    {
        $color = ResultHelper::colorRatio(0.25);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);
    }

    public function testColorRatioWithThreeQuarterRatio(): void
    {
        $color = ResultHelper::colorRatio(0.75);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);
    }

    public function testColorRatioReturnsValidHexFormat(): void
    {
        $ratios = [0.0, 0.1, 0.3, 0.5, 0.7, 0.9, 1.0];

        foreach ($ratios as $ratio) {
            $color = ResultHelper::colorRatio($ratio);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);
        }
    }

    public function testColorRatioConstantsAreValid(): void
    {
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', ResultHelper::COLOR_LOWEST);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', ResultHelper::COLOR_BEST);
        $this->assertSame('#%02x%02x%02x', ResultHelper::HEX_FORMAT);
    }

    public function testColorRatioWithExtremRatios(): void
    {
        // Test with ratio below 0
        $color = ResultHelper::colorRatio(-0.5);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);

        // Test with ratio above 1
        $color = ResultHelper::colorRatio(1.5);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);
    }
}
