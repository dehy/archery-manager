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
    #[ORM\Column(type: 'integer')]
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

    #[ORM\OneToOne(targetEntity: Result::class, cascade: ['persist', 'remove'])]
    private Result $result;

    #[ORM\Column(type: 'EventParticipationStateType')]
    private string $participationState;

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

    public function getResult(): ?Result
    {
        return $this->result;
    }

    public function setResult(?Result $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getParticipationState(): string
    {
        return $this->participationState;
    }

    public function setParticipationState(string $participationState): self
    {
        $this->participationState = $participationState;

        return $this;
    }
}
