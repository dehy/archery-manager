<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Type\EventParticipationStateType;
use App\Type\LicenseActivityType;
use App\Type\TargetTypeType;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_participations')]
#[Auditable]
#[ApiResource]
#[ApiResource(
    uriTemplate: '/events/{eventId}/participations/{id}',
    operations: [new Get()],
    uriVariables: [
        'eventId' => new Link(toProperty: 'event', fromClass: Event::class),
        'id' => new Link(fromClass: EventParticipation::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/events/{eventId}/participations',
    operations: [new GetCollection()],
    uriVariables: [
        'eventId' => new Link(toProperty: 'event', fromClass: Event::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/participations/{id}',
    operations: [new Get()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'participant', fromClass: Licensee::class),
        'id' => new Link(fromClass: EventParticipation::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/participations',
    operations: [new GetCollection()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'participant', fromClass: Licensee::class),
    ]
)]
class EventParticipation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    public Event $event;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'eventParticipations')]
    #[ORM\JoinColumn(nullable: false)]
    public Licensee $participant;

    #[ORM\Column(type: Types::STRING, enumType: LicenseActivityType::class, nullable: true)]
    public ?LicenseActivityType $activity = null;

    #[ORM\Column(type: Types::STRING, enumType: TargetTypeType::class, nullable: true)]
    public ?TargetTypeType $targetType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $departure = null;

    #[ORM\OneToOne(targetEntity: Result::class, cascade: ['persist', 'remove'])]
    public ?Result $result = null;

    #[ORM\Column(type: Types::STRING, enumType: EventParticipationStateType::class)]
    public EventParticipationStateType $participationState = EventParticipationStateType::Interested;

    #[ORM\ManyToOne(targetEntity: Licensee::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?Licensee $licensee = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $registrationDate = null;

    public function __construct()
    {
        $this->event = new Event();
        $this->participant = new Licensee();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
