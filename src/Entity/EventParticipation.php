<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\EventParticipationRepository;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventParticipationRepository::class)]
#[Auditable]
#[ApiResource]
class EventParticipation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private Event $event;

    #[
        ORM\ManyToOne(
            targetEntity: Licensee::class,
            inversedBy: 'eventParticipations',
        ),
    ]
    #[ORM\JoinColumn(nullable: false)]
    private Licensee $participant;

    #[ORM\Column(type: 'LicenseActivityType')]
    private ?string $activity = null;

    #[ORM\Column(type: 'TargetTypeType')]
    private ?string $targetType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $departure = null;

    #[ORM\OneToOne(targetEntity: Result::class, cascade: ['persist', 'remove'])]
    private Result $result;

    #[ORM\Column(type: 'EventParticipationStateType')]
    private ?string $participationState = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getParticipant(): ?Licensee
    {
        return $this->participant;
    }

    public function setParticipant(?Licensee $participant): self
    {
        $this->participant = $participant;

        return $this;
    }

    public function getActivity(): ?string
    {
        return $this->activity;
    }

    public function setActivity(?string $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    public function getTargetType(): ?string
    {
        return $this->targetType;
    }

    public function setTargetType(?string $targetType): self
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getDeparture(): ?int
    {
        return $this->departure;
    }

    public function setDeparture(?int $departure): self
    {
        $this->departure = $departure;

        return $this;
    }

    public function getResult(): ?Result
    {
        return $this->result;
    }

    public function setResult(?Result $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getParticipationState(): ?string
    {
        return $this->participationState;
    }

    public function setParticipationState(string $participationState): self
    {
        $this->participationState = $participationState;

        return $this;
    }
}
