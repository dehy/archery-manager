<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\EventProcessor;
use App\State\EventProvider;
use App\Type\DisciplineType;
use App\Type\EventStatusType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: \App\Repository\EventRepository::class)]
#[ORM\Table(name: 'events')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'contest' => ContestEvent::class,
    'training' => TrainingEvent::class,
    'free_training' => FreeTrainingEvent::class,
    'other' => Event::class,
])]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['event:list']],
            filters: ['event.search_filter', 'event.date_filter', 'event.order_filter']
        ),
        new Get(normalizationContext: ['groups' => ['event:read']]),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['event:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['event:write']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    provider: EventProvider::class,
    processor: EventProcessor::class
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'discipline' => 'exact'])]
#[ApiFilter(DateFilter::class, properties: ['startsAt', 'endsAt'])]
#[ApiFilter(OrderFilter::class, properties: ['startsAt', 'name'])]
class Event implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    #[Groups(['event:read', 'event:list'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: true)]
    #[ApiProperty(types: ['https://schema.org/organizer'])]
    #[Groups(['event:read', 'event:list', 'event:write'])]
    public ?Club $club = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[ApiProperty(types: 'https://schema.org/name')]
    #[Groups(['event:read', 'event:list', 'event:write'])]
    public string $name = '';

    #[ORM\Column(type: Types::STRING, enumType: DisciplineType::class, nullable: true)]
    #[Groups(['event:read', 'event:list', 'event:write'])]
    public ?DisciplineType $discipline = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['event:read', 'event:write'])]
    public bool $allDay = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[ApiProperty(types: 'https://schema.org/startDate')]
    #[Groups(['event:read', 'event:list', 'event:write'])]
    public \DateTimeImmutable $startsAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[ApiProperty(types: 'https://schema.org/endDate')]
    #[Groups(['event:read', 'event:list', 'event:write'])]
    public \DateTimeImmutable $endsAt;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventParticipation::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['event:read'])]
    public Collection $participations;

    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'events')]
    #[ORM\JoinTable(name: 'event_groups')]
    public Collection $assignedGroups;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Gedmo\Slug(fields: ['name', 'startsAt'])]
    public string $slug = '';

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    public ?string $latitude = null;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    public ?string $longitude = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->startsAt = new \DateTimeImmutable();
        $this->endsAt = new \DateTimeImmutable();
        $this->participations = new ArrayCollection();
        $this->assignedGroups = new ArrayCollection();
        $this->attendees = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf(
            '%s - %s',
            $this->startsAt->format('d/m/Y'),
            $this->name,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ApiProperty(types: 'https://schema.org/schedule')]
    public $schedule;

    #[ORM\Column(enumType: EventStatusType::class)]
    #[ApiProperty(types: 'https://schema.org/eventStatus')]
    public EventStatusType $status = EventStatusType::Scheduled;

    #[ORM\Embedded(class: PostalAddress::class, columnPrefix: 'address_')]
    #[ApiProperty(types: 'https://schema.org/location')]
    #[Groups(['event:read', 'event:write'])]
    public ?PostalAddress $address = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[ApiProperty(types: 'https://schema.org/startDate')]
    public ?\DateTimeImmutable $startDate = null;

    #[ApiProperty(types: 'https://schema.org/array')]
    public Collection $attendees;
}
