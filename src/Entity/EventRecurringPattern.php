<?php

namespace App\Entity;

use App\Repository\EventRecurringPatternRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRecurringPatternRepository::class)]
class EventRecurringPattern
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'recurringPatterns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(type: 'RecurringType')]
    private ?string $recurringType = null;

    #[ORM\Column(nullable: false)]
    private ?int $separationCount = 0;

    #[ORM\Column(nullable: true)]
    private ?int $maxNumOfOccurrences = null;

    #[ORM\Column(nullable: true)]
    private ?int $dayOfWeek = null;

    #[ORM\Column(nullable: true)]
    private ?int $weekOfMonth = null;

    #[ORM\Column(nullable: true)]
    private ?int $dayOfMonth = null;

    #[ORM\Column(nullable: true)]
    private ?int $monthOfYear = null;

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getRecurringType(): ?string
    {
        return $this->recurringType;
    }

    public function setRecurringType(string $recurringType): static
    {
        $this->recurringType = $recurringType;

        return $this;
    }

    public function getSeparationCount(): ?int
    {
        return $this->separationCount;
    }

    public function setSeparationCount(?int $separationCount): static
    {
        $this->separationCount = $separationCount;

        return $this;
    }

    public function getMaxNumOfOccurrences(): ?int
    {
        return $this->maxNumOfOccurrences;
    }

    public function setMaxNumOfOccurrences(?int $maxNumOfOccurrences): static
    {
        $this->maxNumOfOccurrences = $maxNumOfOccurrences;

        return $this;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(?int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getWeekOfMonth(): ?int
    {
        return $this->weekOfMonth;
    }

    public function setWeekOfMonth(?int $weekOfMonth): static
    {
        $this->weekOfMonth = $weekOfMonth;

        return $this;
    }

    public function getDayOfMonth(): ?int
    {
        return $this->dayOfMonth;
    }

    public function setDayOfMonth(?int $dayOfMonth): static
    {
        $this->dayOfMonth = $dayOfMonth;

        return $this;
    }

    public function getMonthOfYear(): ?int
    {
        return $this->monthOfYear;
    }

    public function setMonthOfYear(?int $monthOfYear): static
    {
        $this->monthOfYear = $monthOfYear;

        return $this;
    }
}
