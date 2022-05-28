<?php

namespace App\Entity;

use App\Repository\EventParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventParticipationRepository::class)]
class EventParticipation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: "participations")]
    #[ORM\JoinColumn(nullable: false)]
    private $event;

    #[
        ORM\ManyToOne(
            targetEntity: Licensee::class,
            inversedBy: "eventParticipations"
        )
    ]
    #[ORM\JoinColumn(nullable: false)]
    private $participant;

    #[ORM\OneToOne(targetEntity: Result::class, cascade: ["persist", "remove"])]
    private $result;

    #[ORM\Column(type: "boolean")]
    private $present;

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

    public function getPresent(): ?bool
    {
        return $this->present;
    }

    public function setPresent(bool $present): self
    {
        $this->present = $present;

        return $this;
    }
}
