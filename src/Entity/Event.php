<?php

declare(strict_types=1);

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
    private const string DATE_FORMAT = 'd/m/Y';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'events')]
    #[ORM\JoinColumn]
    protected ?Club $club = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(type: 'DisciplineType')]
    protected ?string $discipline = null;

    #[ORM\Column]
    protected bool $allDay = false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)]
    protected ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $address = null;

    /**
     * @var Collection<int, EventParticipation>
     */
    #[ORM\OneToMany(targetEntity: EventParticipation::class, mappedBy: 'event', orphanRemoval: true),]
    protected Collection $participations;

    /**
     * @var Collection<int, EventAttachment>
     */
    #[ORM\OneToMany(targetEntity: EventAttachment::class, mappedBy: 'event')]
    protected Collection $attachments;

    /**
     * @var Collection<int, Group>
     */
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

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->assignedGroups = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf(
            '%s - %s - %s',
            $this->getStartsAt()->format(self::DATE_FORMAT),
            EventType::getReadableValue(static::class),
            $this->getName(),
        );
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

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): self
    {
        $this->startsAt = $startsAt;

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
        // set the owning side to null (unless already changed)
        if ($this->participations->removeElement($participation) && $participation->getEvent() === $this) {
            $participation->setEvent(null);
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
        // set the owning side to null (unless already changed)
        if ($this->attachments->removeElement($attachment) && $attachment->getEvent() === $this) {
            $attachment->setEvent(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, EventAttachment>
     */
    public function getAttachments(?string $type = null): Collection
    {
        if (null !== $type && '' !== $type && '0' !== $type) {
            return $this->attachments
                ->filter(static fn (EventAttachment $attachment): bool => $attachment->getType() === $type);
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
                \sprintf('%s-%s', $this->getStartsAt()->format('d-m-Y'), $this->getTitle())
            )
        );
        $this->setUpdatedAt(new \DateTimeImmutable());
    }

    public function getTitle(): string
    {
        return \sprintf(
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
        return $this->getStartsAt()->format(self::DATE_FORMAT) !== $this->getEndsAt()->format(self::DATE_FORMAT);
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
