<?php

namespace App\Helper;

use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;

class MapHelper
{
    private const MAPBOX_URL = 'https://api.mapbox.com/styles/v1/{username}/{style_id}/static/{layout}{lon},{lat},{zoom}/{width}x{height}';
    private array $defaultParameters = [];

    public function __construct(
        string $username,
        private readonly string $accessToken,
        private readonly Provider $mapboxGeocoder
    ) {
        $this->defaultParameters = [
            'username' => $username,
            'style_id' => 'streets-v11',
            'layout' => '',
            'zoom' => '14',
            'width' => '800',
            'height' => '600',
        ];
    }

    public function urlForAddress(string $address): ?string
    {
        $coordinates = $this->mapboxGeocoder->geocodeQuery(GeocodeQuery::create($address));
        if (0 === $coordinates->count()) {
            return null;
        }

        $longitude = $coordinates->first()->getCoordinates()->getLongitude();
        $latitude = $coordinates->first()->getCoordinates()->getLatitude();

        return $this->urlForLongLat($longitude, $latitude);
    }

    public function urlForLongLat(float $long, float $lat): string
    {
        $url = self::MAPBOX_URL;

        $parameters = $this->defaultParameters;
        $parameters['lon'] = $long;
        $parameters['lat'] = $lat;
        foreach ($parameters as $key => $value) {
            if ('layout' === $key) {
                $value = sprintf('pin-s-embassy+000(%s,%s)/', $long, $lat);
            }
            $url = str_replace(sprintf('{%s}', $key), $value, $url);
        }

        return $url . '?access_token=' . $this->accessToken;
    }
}
