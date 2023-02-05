<?php

namespace App\Entity;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventKindType;
use App\Repository\EventRepository;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    EventKindType::CONTEST_OFFICIAL => ContestEvent::class,
    EventKindType::CONTEST_HOBBY => HobbyContestEvent::class,
    EventKindType::TRAINING => TrainingEvent::class,
    EventKindType::OTHER => Event::class,
])]
#[ORM\HasLifecycleCallbacks]
class Event implements \Stringable
{
    #[ORM\Column(type: 'string', length: 255)]
    protected string $name;

    // TODO remove $kind and use get_class($entity) to test event kind
    #[ORM\Column(type: 'EventKindType')]
    protected string $kind;

    #[ORM\Column(type: 'DisciplineType')]
    protected string $discipline;

    #[ORM\Column]
    protected bool $allDay = false;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $startsAt;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $endsAt;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $address;

    #[
        ORM\OneToMany(
            mappedBy: 'event',
            targetEntity: EventParticipation::class,
            orphanRemoval: true,
        ),
    ]
    protected Collection $participations;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventAttachment::class)]
    protected Collection $attachments;

    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'events')]
    protected Collection $assignedGroups;

    #[ORM\Column(length: 255)]
    protected ?string $slug = null;

    #[ORM\Column(length: 16, nullable: true)]
    protected ?string $latitude = null;

    #[ORM\Column(length: 16, nullable: true)]
    protected ?string $longitude = null;

    #[ORM\Column]
    protected ?\DateTimeImmutable $updatedAt = null;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->assignedGroups = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s - %s',
            $this->getStartsAt()->format('d/m/Y'),
            EventKindType::getReadableValue($this->getKind()),
            $this->getName(),
        );
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

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind($kind): self
    {
        $this->kind = $kind;

        return $this;
    }

    public function getDiscipline(): string
    {
        return $this->discipline;
    }

    public function setDiscipline(string $discipline): Event
    {
        $this->discipline = $discipline;

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

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, PracticeAdviceAttachment>
     */
    public function getAttachments(?string $type = null): Collection
    {
        if ($type) {
            return $this->attachments
                ->filter(fn (EventAttachment $attachment) => $attachment->getType() === $type);
        }

        return $this->attachments;
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

    /**
     * @return Collection<int, EventParticipation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
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

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeImmutable $endsAt): self
    {
        $this->endsAt = $endsAt;

        return $this;
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
        $this->setUpdatedAt(new \DateTimeImmutable());
    }

    public function getTitle(): string
    {
        return sprintf(
            '%s %s %s',
            ucfirst(EventKindType::getReadableValue($this->getKind())),
            lcfirst(DisciplineType::getReadableValue($this->getDiscipline())),
            $this->getName()
        );
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): self
    {
        $this->allDay = $allDay;

        return $this;
    }

    public function spanMultipleDays(): bool
    {
        return $this->getStartsAt()->format('d/m/Y') !== $this->getEndsAt()->format('d/m/Y');
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
