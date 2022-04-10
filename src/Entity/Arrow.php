<?php

namespace App\Entity;

use App\Repository\ArrowRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArrowRepository::class)]
class Arrow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'arrows')]
    #[ORM\JoinColumn(nullable: false)]
    private $owner;

    #[ORM\Column(type: 'ArrowType')]
    private $type;

    #[ORM\Column(type: 'integer')]
    private $spine;

    #[ORM\Column(type: 'FletchingType')]
    private $fletching;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getType()
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

    public function getFletching()
    {
        return $this->fletching;
    }

    public function setFletching($fletching): self
    {
        $this->fletching = $fletching;

        return $this;
    }
}
