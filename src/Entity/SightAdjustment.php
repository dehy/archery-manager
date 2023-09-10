<?php

namespace App\Entity;

use App\Repository\SightAdjustmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SightAdjustmentRepository::class)]
class SightAdjustment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Bow::class, inversedBy: 'sightAdjustments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bow $bow = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $distance = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $setting = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBow(): ?Bow
    {
        return $this->bow;
    }

    public function setBow(?Bow $bow): self
    {
        $this->bow = $bow;

        return $this;
    }

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getSetting(): ?string
    {
        return $this->setting;
    }

    public function setSetting(string $setting): self
    {
        $this->setting = $setting;

        return $this;
    }
}
