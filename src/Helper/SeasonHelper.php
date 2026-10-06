<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Season;
use App\Security\Api\ApiRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SeasonHelper
{
    private const string SESSION_KEY = 'selectedSeason';

    private const int MIN_SEASON = 2000;

    private const int MAX_SEASON = 2100;

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * The selected season: from the session on the web, from the X-Season
     * header on the stateless mobile API. Defaults to the current season.
     */
    public function getSelectedSeason(): int
    {
        $request = $this->requestStack->getCurrentRequest();
        if (ApiRequest::is($request)) {
            return $this->getSeasonFromHeader((string) $request?->headers->get(ApiRequest::HEADER_SEASON));
        }

        return $this->requestStack->getSession()->get(self::SESSION_KEY)
            ?? Season::seasonForDate(new \DateTimeImmutable());
    }

    public function setSelectedSeason(int $season): void
    {
        if (ApiRequest::is($this->requestStack->getCurrentRequest())) {
            return;
        }

        $this->requestStack->getSession()->set(self::SESSION_KEY, $season);
    }

    private function getSeasonFromHeader(string $header): int
    {
        if ('' === $header) {
            return Season::seasonForDate(new \DateTimeImmutable());
        }

        $season = filter_var($header, \FILTER_VALIDATE_INT);
        if (false === $season || $season < self::MIN_SEASON || $season > self::MAX_SEASON) {
            throw new BadRequestHttpException(\sprintf('Invalid %s header.', ApiRequest::HEADER_SEASON));
        }

        return $season;
    }
}
