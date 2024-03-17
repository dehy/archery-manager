<?php

namespace App\Entity;

use App\DBAL\Types\EventType;
use App\Repository\EventRepository;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap([
    'contest_official' => ContestEvent::class,
    'contest_hobby' => HobbyContestEvent::class,
    'training' => TrainingEvent::class,
    'free_training' => FreeTrainingEvent::class,
    'other' => Event::class,
])]
#[ORM\HasLifecycleCallbacks]
class Event implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'events')]
    #[ORM\JoinColumn]
    protected ?Club $club = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'DisciplineType')]
    protected ?string $discipline = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATE_IMMUTABLE)]
    protected ?\DateTimeImmutable $startDate;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATE_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $endDate;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(nullable: false)]
    protected bool $fullDayEvent = false;

    #[ORM\Column(nullable: false)]
    protected bool $recurring = false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    protected ?string $address = null;

    /**
     * @var Collection<int, EventParticipation>|EventParticipation[]
     */
    #[
        ORM\OneToMany(
            mappedBy: 'event',
            targetEntity: EventParticipation::class,
            orphanRemoval: true,
        ),
    ]
    protected Collection $participations;

    /**
     * @var Collection<int, EventAttachment>|EventAttachment[]
     */
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

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    protected ?User $createdBy = null;

    #[ORM\Column(nullable: false)]
    protected ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    protected ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    protected ?Event $parentEvent = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventInstanceException::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $eventInstanceExceptions;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventRecurringPattern::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $recurringPatterns;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->assignedGroups = new ArrayCollection();
        $this->eventInstanceExceptions = new ArrayCollection();
        $this->recurringPatterns = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s - %s',
            $this->getStartTime()->format('d/m/Y'),
            EventType::getReadableValue(static::class),
            $this->getName(),
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(Club $club): self
    {
        $this->club = $club;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function isFullDayEvent(): bool
    {
        return $this->fullDayEvent;
    }

    public function setFullDayEvent(bool $fullDayEvent): self
    {
        $this->fullDayEvent = $fullDayEvent;

        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->recurring;
    }

    public function setRecurring(bool $recurring): self
    {
        $this->recurring = $recurring;

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

    public function getDiscipline(): string
    {
        return $this->discipline;
    }

    public function setDiscipline(string $discipline): self
    {
        $this->discipline = $discipline;

        return $this;
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
     * @return Collection<int, EventAttachment>
     */
    public function getAttachments(string $type = null): Collection
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

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $slugify = new Slugify();
        $this->setSlug(
            $slugify->slugify(
                sprintf('%s-%s', $this->getStartDate()->format('d-m-Y'), $this->getName())
            )
        );
        $this->setUpdatedAt(new \DateTimeImmutable());
    }

    public function getTitle(): string
    {
        return sprintf(
            '%s %s',
            ucfirst(EventType::getReadableValue(static::class)),
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

    public function spanMultipleDays(): bool
    {
        return $this->getStartDate()->format('Y-m-d') !== $this->getEndDate()->format('Y-m-d');
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
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

    public function getParentEvent(): ?self
    {
        return $this->parentEvent;
    }

    public function setParentEvent(?self $parentEvent): self
    {
        $this->parentEvent = $parentEvent;

        return $this;
    }

    /**
     * @return Collection<int, EventInstanceException>
     */
    public function getEventInstanceExceptions(): Collection
    {
        return $this->eventInstanceExceptions;
    }

    public function addEventInstanceException(EventInstanceException $eventInstanceException): static
    {
        if (!$this->eventInstanceExceptions->contains($eventInstanceException)) {
            $this->eventInstanceExceptions->add($eventInstanceException);
            $eventInstanceException->setEvent($this);
        }

        return $this;
    }

    public function removeEventInstanceException(EventInstanceException $eventInstanceException): static
    {
        if ($this->eventInstanceExceptions->removeElement($eventInstanceException)) {
            // set the owning side to null (unless already changed)
            if ($eventInstanceException->getEvent() === $this) {
                $eventInstanceException->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EventRecurringPattern>
     */
    public function getRecurringPatterns(): Collection
    {
        return $this->recurringPatterns;
    }

    public function addRecurringPattern(EventRecurringPattern $recurringPattern): static
    {
        if (!$this->recurringPatterns->contains($recurringPattern)) {
            $this->recurringPatterns->add($recurringPattern);
            $recurringPattern->setEvent($this);
        }

        return $this;
    }

    public function removeRecurringPattern(EventRecurringPattern $recurringPattern): static
    {
        if ($this->recurringPatterns->removeElement($recurringPattern)) {
            // set the owning side to null (unless already changed)
            if ($recurringPattern->getEvent() === $this) {
                $recurringPattern->setEvent(null);
            }
        }

        return $this;
    }
}
