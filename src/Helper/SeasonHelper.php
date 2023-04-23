<?php

namespace App\Helper;

use App\Entity\Season;
use Symfony\Component\HttpFoundation\RequestStack;

class SeasonHelper
{
    private const SESSION_KEY = 'selectedSeason';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getSelectedSeason(): int
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY)
            ?? Season::seasonForDate(new \DateTimeImmutable());
    }

    public function setSelectedSeason(int $season): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $season);
    }
}
