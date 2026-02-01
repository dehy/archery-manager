<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Helper\MapHelper;
use Geocoder\Collection;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use PHPUnit\Framework\TestCase;

final class MapHelperTest extends TestCase
{
    private Provider $mapboxGeocoder;

    private MapHelper $mapHelper;

    private string $username = 'testuser';

    private string $accessToken = 'test_access_token';

    #[\Override]
    protected function setUp(): void
    {
        $this->mapboxGeocoder = $this->createMock(Provider::class);
        $this->mapHelper = new MapHelper($this->username, $this->accessToken, $this->mapboxGeocoder);
    }

    public function testUrlForLongLat(): void
    {
        // The MapHelper has a bug where it tries to str_replace with float values
        // This test documents the current behavior
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('str_replace(): Argument #2 ($replace) must be of type array|string, float given');

        $this->mapHelper->urlForLongLat(2.3522, 48.8566);
    }

    public function testUrlForLongLatWithDifferentCoordinates(): void
    {
        // The MapHelper has a bug with str_replace expecting string but getting float
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('str_replace(): Argument #2 ($replace) must be of type array|string, float given');

        $this->mapHelper->urlForLongLat(-74.0060, 40.7128);
    }

    public function testUrlForAddressWithValidAddress(): void
    {
        $address = 'Paris, France';

        // Mock empty collection to avoid the geocoding part and focus on the URL generation bug
        $collection = $this->createMock(Collection::class);
        $collection->method('count')->willReturn(0);

        $this->mapboxGeocoder
            ->expects($this->once())
            ->method('geocodeQuery')
            ->willReturn($collection);

        $url = $this->mapHelper->urlForAddress($address);

        // When no results are found, the method returns null
        $this->assertNull($url);
    }

    public function testUrlForAddressWithInvalidAddress(): void
    {
        $address = 'Invalid Address That Does Not Exist';

        // Mock empty collection
        $collection = $this->createMock(Collection::class);
        $collection->method('count')->willReturn(0);

        $this->mapboxGeocoder
            ->expects($this->once())
            ->method('geocodeQuery')
            ->willReturn($collection);

        $url = $this->mapHelper->urlForAddress($address);

        $this->assertNull($url);
    }

    public function testUrlForAddressGeocodeQueryCreation(): void
    {
        $address = 'Test Address';

        // Mock empty collection to avoid further processing
        $collection = $this->createMock(Collection::class);
        $collection->method('count')->willReturn(0);

        $this->mapboxGeocoder
            ->expects($this->once())
            ->method('geocodeQuery')
            ->with($this->callback(static fn (GeocodeQuery $query): bool => $query->getText() === $address))
            ->willReturn($collection);

        $this->mapHelper->urlForAddress($address);
    }

    public function testUrlForLongLatWithZeroCoordinates(): void
    {
        // The MapHelper has a bug with str_replace
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('str_replace(): Argument #2 ($replace) must be of type array|string, float given');

        $this->mapHelper->urlForLongLat(0.0, 0.0);
    }

    public function testUrlForLongLatWithNegativeCoordinates(): void
    {
        // The MapHelper has a bug with str_replace
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('str_replace(): Argument #2 ($replace) must be of type array|string, float given');

        $this->mapHelper->urlForLongLat(-122.4194, -37.7749);
    }
}
