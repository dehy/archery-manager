<?php

namespace App\Scrapper;

use App\DBAL\Types\DisciplineType;

class FftaEvent
{
    private string $name;
    private \DateTimeImmutable $from;
    private \DateTimeImmutable $to;
    private string $location;
    private string $discipline;
    private string $specifics;
    private string $url;

    public function setName(string $name): FftaEvent
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setFrom(\DateTimeImmutable $from): FftaEvent
    {
        $this->from = $from;

        return $this;
    }

    public function getFrom(): \DateTimeImmutable
    {
        return $this->from;
    }

    public function setTo(\DateTimeImmutable $to): FftaEvent
    {
        $this->to = $to;

        return $this;
    }

    public function getTo(): \DateTimeImmutable
    {
        return $this->to;
    }

    public function setLocation(string $location): FftaEvent
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setDiscipline(string $discipline): FftaEvent
    {
        DisciplineType::assertValidChoice($discipline);
        $this->discipline = $discipline;

        return $this;
    }

    public function getDiscipline(): string
    {
        return $this->discipline;
    }

    public function getSpecifics(): string
    {
        return $this->specifics;
    }

    public function setSpecifics(string $specifics): FftaEvent
    {
        $this->specifics = $specifics;

        return $this;
    }

    public function setUrl(string $url): FftaEvent
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
