<?php

namespace App\Entity;

use App\Repository\ArrowRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArrowRepository::class)]
class Arrow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'arrows')]
    #[ORM\JoinColumn(nullable: false)]
    private Licensee $owner;

    #[ORM\Column(type: 'ArrowType')]
    private string $type;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private int $spine;

    #[ORM\Column(type: 'FletchingType')]
    private string $fletching;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?Licensee
    {
        return $this->owner;
    }

    public function setOwner(?Licensee $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSpine(): ?int
    {
        return $this->spine;
    }

    public function setSpine(int $spine): self
    {
        $this->spine = $spine;

        return $this;
    }

    public function getFletching(): string
    {
        return $this->fletching;
    }

    public function setFletching($fletching): self
    {
        $this->fletching = $fletching;

        return $this;
    }
}
