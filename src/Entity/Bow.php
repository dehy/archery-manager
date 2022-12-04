<?php

namespace App\Entity;

use App\Repository\BowRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BowRepository::class)]
class Bow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'bows')]
    #[ORM\JoinColumn(nullable: false)]
    private Licensee $owner;

    #[ORM\Column(type: 'BowType')]
    private string $type;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $limbSize = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $limbStrength = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $braceHeight = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $drawLength = null;

    #[
        ORM\OneToMany(
            mappedBy: 'bow',
            targetEntity: SightAdjustment::class,
            orphanRemoval: true,
        ),
    ]
    private Collection $sightAdjustments;

    public function __construct()
    {
        $this->sightAdjustments = new ArrayCollection();
    }

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

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getLimbSize(): ?int
    {
        return $this->limbSize;
    }

    public function setLimbSize(?int $limbSize): self
    {
        $this->limbSize = $limbSize;

        return $this;
    }

    public function getLimbStrength(): ?int
    {
        return $this->limbStrength;
    }

    public function setLimbStrength(?int $limbStrength): self
    {
        $this->limbStrength = $limbStrength;

        return $this;
    }

    public function getBraceHeight(): ?float
    {
        return $this->braceHeight;
    }

    public function setBraceHeight(?float $braceHeight): self
    {
        $this->braceHeight = $braceHeight;

        return $this;
    }

    public function getDrawLength(): ?int
    {
        return $this->drawLength;
    }

    public function setDrawLength(?int $drawLength): self
    {
        $this->drawLength = $drawLength;

        return $this;
    }

    /**
     * @return Collection<int, SightAdjustment>
     */
    public function getSightAdjustments(): Collection
    {
        return $this->sightAdjustments;
    }

    public function addSightAdjustment(SightAdjustment $sightAdjustment): self
    {
        if (!$this->sightAdjustments->contains($sightAdjustment)) {
            $this->sightAdjustments[] = $sightAdjustment;
            $sightAdjustment->setBow($this);
        }

        return $this;
    }

    public function removeSightAdjustment(
        SightAdjustment $sightAdjustment,
    ): self {
        if ($this->sightAdjustments->removeElement($sightAdjustment)) {
            // set the owning side to null (unless already changed)
            if ($sightAdjustment->getBow() === $this) {
                $sightAdjustment->setBow(null);
            }
        }

        return $this;
    }
}
