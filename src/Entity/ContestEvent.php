<?php

declare(strict_types=1);

namespace App\Entity;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventAttachmentType;
use App\DBAL\Types\EventParticipationStateType;
use App\DBAL\Types\EventScopeType;
use App\DBAL\Types\EventType;
use App\Repository\ContestEventRepository;
use App\Scrapper\FftaEvent;
use App\Scrapper\FftaPublicEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContestEventRepository::class)]
class ContestEvent extends Event
{
    #[ORM\Column(type: 'ContestType', nullable: true)]
    private ?string $contestType = null;

    #[ORM\Column(nullable: true, unique: true)]
    private ?int $fftaEventId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fftaComiteDepartemental = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fftaComiteRegional = null;

    /**
     * @var Collection<int, Result>
     */
    #[ORM\OneToMany(targetEntity: Result::class, mappedBy: 'event')]
    private Collection $results;

    public function __construct()
    {
        parent::__construct();
        $this->results = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf(
            '%s - %s %s - %s',
            $this->getStartsAt()->format('d/m/Y'),
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
            ->setEndsAt($fftaEvent->getTo())
            ->setName($fftaEvent->getName())
            ->setStartsAt($fftaEvent->getFrom());

        return $event;
    }

    public static function fromFftaPublicEvent(FftaPublicEvent $event): self
    {
        $contest = new self();
        $contest
            ->setFftaEventId($event->fftaEventId)
            ->setFftaComiteDepartemental($event->comiteDepartemental)
            ->setFftaComiteRegional($event->comiteRegional)
            ->setContestType($event->contestType ?? ContestType::INDIVIDUAL)
            ->setAddress($event->address)
            ->setDiscipline($event->discipline)
            ->setEndsAt($event->endsAt)
            ->setName($event->name)
            ->setStartsAt($event->startsAt)
            ->setScope(EventScopeType::DEPARTMENTAL);

        return $contest;
    }

    public function getFftaEventId(): ?int
    {
        return $this->fftaEventId;
    }

    public function setFftaEventId(?int $fftaEventId): self
    {
        $this->fftaEventId = $fftaEventId;

        return $this;
    }

    public function getFftaComiteDepartemental(): ?string
    {
        return $this->fftaComiteDepartemental;
    }

    public function setFftaComiteDepartemental(?string $fftaComiteDepartemental): self
    {
        $this->fftaComiteDepartemental = $fftaComiteDepartemental;

        return $this;
    }

    public function getFftaComiteRegional(): ?string
    {
        return $this->fftaComiteRegional;
    }

    public function setFftaComiteRegional(?string $fftaComiteRegional): self
    {
        $this->fftaComiteRegional = $fftaComiteRegional;

        return $this;
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
        // set the owning side to null (unless already changed)
        if ($this->results->removeElement($result) && $result->getEvent() === $this) {
            $result->setEvent(null);
        }

        return $this;
    }

    public function canImportResults(): bool
    {
        return $this->getDiscipline() && $this->getContestType();
    }

    public function getSeason(): int
    {
        $month = (int) $this->getStartsAt()->format('m');
        $year = (int) $this->getStartsAt()->format('Y');
        if ($month >= 9 && $month <= 12) {
            return $year + 1;
        }

        return $year;
    }

    public function hasMandate(): bool
    {
        return $this->getAttachments()->exists(
            static fn (int $key, EventAttachment $attachment): bool => EventAttachmentType::MANDATE === $attachment->getType()
        );
    }

    public function hasResults(): bool
    {
        return $this->getAttachments()->exists(
            static fn (int $key, EventAttachment $attachment): bool => EventAttachmentType::RESULTS === $attachment->getType()
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

    #[\Override]
    public function getTitle(): string
    {
        return \sprintf(
            '%s %s %s',
            ucfirst(EventType::getReadableValue(static::class)),
            lcfirst(DisciplineType::getReadableValue($this->getDiscipline())),
            $this->getName()
        );
    }
}
