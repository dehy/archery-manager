<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClubEquipmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubEquipmentRepository::class)]
class ClubEquipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Club::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Club $club = null;

    #[ORM\Column(type: 'ClubEquipmentType')]
    private ?string $type = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $serialNumber = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $count = null;

    #[ORM\Column(type: 'BowType', nullable: true)]
    private ?string $bowType = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $limbSize = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $limbStrength = null;

    #[ORM\Column(type: 'ArrowType', nullable: true)]
    private ?string $arrowType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $arrowLength = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $arrowSpine = null;

    #[ORM\Column(type: 'FletchingType', nullable: true)]
    private ?string $fletchingType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isAvailable = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, EquipmentLoan>
     */
    #[ORM\OneToMany(mappedBy: 'equipment', targetEntity: EquipmentLoan::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['startDate' => 'DESC'])]
    private Collection $loans;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->loans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber): self
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getBowType(): ?string
    {
        return $this->bowType;
    }

    public function setBowType(?string $bowType): self
    {
        $this->bowType = $bowType;

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

    public function getArrowType(): ?string
    {
        return $this->arrowType;
    }

    public function setArrowType(?string $arrowType): self
    {
        $this->arrowType = $arrowType;

        return $this;
    }

    public function getArrowLength(): ?int
    {
        return $this->arrowLength;
    }

    public function setArrowLength(?int $arrowLength): self
    {
        $this->arrowLength = $arrowLength;

        return $this;
    }

    public function getArrowSpine(): ?string
    {
        return $this->arrowSpine;
    }

    public function setArrowSpine(?string $arrowSpine): self
    {
        $this->arrowSpine = $arrowSpine;

        return $this;
    }

    public function getFletchingType(): ?string
    {
        return $this->fletchingType;
    }

    public function setFletchingType(?string $fletchingType): self
    {
        $this->fletchingType = $fletchingType;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): self
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, EquipmentLoan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(EquipmentLoan $loan): self
    {
        if (!$this->loans->contains($loan)) {
            $this->loans[] = $loan;
            $loan->setEquipment($this);
        }

        return $this;
    }

    public function removeLoan(EquipmentLoan $loan): self
    {
        if ($this->loans->removeElement($loan) && $loan->getEquipment() === $this) {
            $loan->setEquipment(null);
        }

        return $this;
    }

    public function getCurrentLoan(): ?EquipmentLoan
    {
        foreach ($this->loans as $loan) {
            if (null === $loan->getReturnDate()) {
                return $loan;
            }
        }

        return null;
    }

    public function isCurrentlyLoaned(): bool
    {
        return $this->getCurrentLoan() instanceof EquipmentLoan;
    }
}
