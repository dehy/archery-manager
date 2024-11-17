<?php

declare(strict_types=1);

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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setFrom(\DateTimeImmutable $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function getFrom(): \DateTimeImmutable
    {
        return $this->from;
    }

    public function setTo(\DateTimeImmutable $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function getTo(): \DateTimeImmutable
    {
        return $this->to;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setDiscipline(string $discipline): self
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

    public function setSpecifics(string $specifics): self
    {
        $this->specifics = $specifics;

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
