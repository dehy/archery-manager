<?php

namespace App\Entity;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventAttachmentType;
use App\DBAL\Types\EventParticipationStateType;
use App\DBAL\Types\EventType;
use App\Repository\ContestEventRepository;
use App\Scrapper\FftaEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContestEventRepository::class)]
class ContestEvent extends Event
{
    #[ORM\Column(type: 'ContestType', nullable: true)]
    private ?string $contestType = null;

    /**
     * @var Collection<int, Result>|Result[]
     */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Result::class)]
    private Collection $results;

    public function __construct()
    {
        parent::__construct();
        $this->results = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s %s - %s',
            $this->getStartTime()->format('d/m/Y'),
            EventType::getReadableValue(self::class),
            DisciplineType::getReadableValue($this->getDiscipline()),
            $this->getName(),
        );
    }

    public static function fromFftaEvent(FftaEvent $fftaEvent): self
    {
        $event = new self();
        $event
            ->setContestType(ContestType::INDIVIDUAL)
            ->setAddress($fftaEvent->getLocation())
            ->setDiscipline($fftaEvent->getDiscipline())
            ->setEndTime($fftaEvent->getTo())
            ->setName($fftaEvent->getName())
            ->setStartTime($fftaEvent->getFrom());

        return $event;
    }

    public function getContestType(): ?string
    {
        return $this->contestType;
    }

    public function setContestType(string $contestType): self
    {
        $this->contestType = $contestType;

        return $this;
    }

    /**
     * @return Collection<int, Result>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(Result $result): self
    {
        if (!$this->results->contains($result)) {
            $this->results[] = $result;
            $result->setEvent($this);
        }

        return $this;
    }

    public function removeResult(Result $result): self
    {
        if ($this->results->removeElement($result)) {
            // set the owning side to null (unless already changed)
            if ($result->getEvent() === $this) {
                $result->setEvent(null);
            }
        }

        return $this;
    }

    public function canImportResults(): bool
    {
        return $this->getDiscipline() && $this->getContestType();
    }

    public function getSeason(): int
    {
        $month = (int) $this->getStartTime()->format('m');
        $year = (int) $this->getStartTime()->format('Y');
        if ($month >= 9 && $month <= 12) {
            return $year + 1;
        }

        return $year;
    }

    public function hasMandate(): bool
    {
        return $this->getAttachments()->exists(
            fn (int $key, EventAttachment $attachment) => EventAttachmentType::MANDATE === $attachment->getType()
        );
    }

    public function hasResults(): bool
    {
        return $this->getAttachments()->exists(
            fn (int $key, EventAttachment $attachment) => EventAttachmentType::RESULTS === $attachment->getType()
        );
    }

    public function getParticipationsByDeparture(): array
    {
        $departures = [];
        foreach ($this->getParticipations() as $participation) {
            if (EventParticipationStateType::NOT_GOING === $participation->getParticipationState()) {
                continue;
            }
            $departures[$participation->getDeparture() ?? 'non précisé'][] = $participation;
        }

        ksort($departures);

        return $departures;
    }
}
