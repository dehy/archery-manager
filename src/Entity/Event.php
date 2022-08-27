<?php

namespace App\Entity;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventType;
use App\Repository\EventRepository;
use App\Scrapper\FftaEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    private $name;

    #[ORM\Column(type: "datetime_immutable")]
    private $startsAt;

    #[ORM\Column(type: "datetime_immutable")]
    private $endsAt;

    #[ORM\Column(type: "string", length: 255)]
    private $address;

    #[ORM\Column(type: "EventType")]
    private $type;

    #[
        ORM\OneToMany(
            mappedBy: "event",
            targetEntity: EventParticipation::class,
            orphanRemoval: true
        )
    ]
    private $participations;

    #[ORM\OneToMany(mappedBy: "event", targetEntity: Result::class)]
    private $results;

    #[ORM\Column(type: "ContestType", nullable: true)]
    private $contestType;

    #[ORM\Column(type: "DisciplineType")]
    private $discipline;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $mandateFilepath;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $resultFilepath;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->results = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf(
            "%s - %s - %s",
            $this->getStartsAt()->format("d/m/Y"),
            DisciplineType::getReadableValue($this->getDiscipline()),
            $this->getAddress()
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeImmutable $endsAt): self
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, EventParticipation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(EventParticipation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations[] = $participation;
            $participation->setEvent($this);
        }

        return $this;
    }

    public function removeParticipation(EventParticipation $participation): self
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getEvent() === $this) {
                $participation->setEvent(null);
            }
        }

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

    public function getContestType()
    {
        return $this->contestType;
    }

    public function setContestType($contestType): self
    {
        $this->contestType = $contestType;

        return $this;
    }

    public function getDiscipline()
    {
        return $this->discipline;
    }

    public function setDiscipline($discipline): self
    {
        $this->discipline = $discipline;

        return $this;
    }

    public function canImportResults(): bool
    {
        return $this->getDiscipline() && $this->getContestType();
    }

    public function getMandateFilepath(): ?string
    {
        return $this->mandateFilepath;
    }

    public function setMandateFilepath(?string $mandateFilepath): self
    {
        $this->mandateFilepath = $mandateFilepath;

        return $this;
    }

    public function getResultFilepath(): ?string
    {
        return $this->resultFilepath;
    }

    public function setResultFilepath(?string $resultFilepath): self
    {
        $this->resultFilepath = $resultFilepath;

        return $this;
    }

    public static function fromFftaEvent(FftaEvent $fftaEvent): Event
    {
        return (new Event())
            ->setAddress($fftaEvent->getLocation())
            ->setContestType(ContestType::FEDERAL)
            ->setDiscipline($fftaEvent->getDiscipline())
            ->setEndsAt($fftaEvent->getTo())
            ->setName($fftaEvent->getName())
            ->setStartsAt($fftaEvent->getFrom())
            ->setType(EventType::CONTEST_OFFICIAL);
    }

    public function getSeason(): int
    {
        $month = (int) $this->getStartsAt()->format("m");
        $year = (int) $this->getStartsAt()->format("Y");
        if ($month >= 9 && $month <= 12) {
            return $year + 1;
        }
        return $year;
    }
}
