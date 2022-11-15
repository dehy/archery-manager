<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventAttachmentType;
use App\DBAL\Types\EventParticipationStateType;
use App\DBAL\Types\EventType;
use App\Repository\EventRepository;
use App\Scrapper\FftaEvent;
use DateTimeImmutable;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startsAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $endsAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $address;

    #[ORM\Column(type: 'EventType')]
    private string $type;

    #[
        ORM\OneToMany(
            mappedBy: 'event',
            targetEntity: EventParticipation::class,
            orphanRemoval: true,
        ),
    ]
    private Collection $participations;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Result::class)]
    private Collection $results;

    #[ORM\Column(type: 'ContestType', nullable: true)]
    private string $contestType;

    #[ORM\Column(type: 'DisciplineType')]
    private string $discipline;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventAttachment::class)]
    private Collection $attachments;

    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'events')]
    private Collection $assignedGroups;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->results = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->assignedGroups = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s %s - %s',
            $this->getStartsAt()->format('d/m/Y'),
            EventType::getReadableValue($this->getType()),
            DisciplineType::getReadableValue($this->getDiscipline()),
            $this->getAddress(),
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

    public function getStartsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(DateTimeImmutable $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(DateTimeImmutable $endsAt): self
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

    public function removeParticipation(
        EventParticipation $participation,
    ): self {
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

    public static function fromFftaEvent(FftaEvent $fftaEvent): Event
    {
        return (new Event())
            ->setAddress($fftaEvent->getLocation())
            ->setContestType(ContestType::FEDERAL)
            ->setDiscipline($fftaEvent->getDiscipline())
            ->setEndsAt($fftaEvent->getTo())
            ->setName($fftaEvent->getName())
            ->setStartsAt($fftaEvent->getFrom())
            ->setType(EventType::CONTEST_OFFICIAL)
        ;
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

    /**
     * @return Collection<int, PracticeAdviceAttachment>
     */
    public function getAttachments(?string $type = null): Collection
    {
        if ($type) {
            return $this->attachments
                ->filter(fn (EventAttachment $attachment) => $attachment->getType() === $type)
            ;
        }

        return $this->attachments;
    }

    public function addAttachment(EventAttachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setEvent($this);
        }

        return $this;
    }

    public function removeAttachment(EventAttachment $attachment): self
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getEvent() === $this) {
                $attachment->setEvent(null);
            }
        }

        return $this;
    }

    public function hasMandate(): bool
    {
        return $this->getAttachments()->exists(function (int $key, EventAttachment $attachment) {
            return EventAttachmentType::MANDATE === $attachment->getType();
        });
    }

    public function hasResults(): bool
    {
        return $this->getAttachments()->exists(function (int $key, EventAttachment $attachment) {
            return EventAttachmentType::RESULTS === $attachment->getType();
        });
    }

    /**
     * @return Collection<int, Group>
     */
    public function getAssignedGroups(): Collection
    {
        return $this->assignedGroups;
    }

    public function addAssignedGroup(Group $assignedGroup): self
    {
        if (!$this->assignedGroups->contains($assignedGroup)) {
            $this->assignedGroups->add($assignedGroup);
        }

        return $this;
    }

    public function removeAssignedGroup(Group $assignedGroup): self
    {
        $this->assignedGroups->removeElement($assignedGroup);

        return $this;
    }

    public function getTitle(): string
    {
        return sprintf(
            '%s %s %s',
            ucfirst(EventType::getReadableValue($this->getType())),
            lcfirst(DisciplineType::getReadableValue($this->getDiscipline())),
            $this->getName()
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

        return $departures;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function isAllDay(): bool
    {
        if ('00:00' === $this->getStartsAt()->format('H:i') && '00:00' === $this->getEndsAt()->format('H:i')) {
            return true;
        }

        return false;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $slugify = new Slugify();
        $this->setSlug(
            $slugify->slugify(
                sprintf('%s-%s', $this->getStartsAt()->format('d-m-Y'), $this->getTitle())
            )
        );
    }
}
