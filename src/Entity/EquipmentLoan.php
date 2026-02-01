<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EquipmentLoanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipmentLoanRepository::class)]
class EquipmentLoan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ClubEquipment::class, inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ClubEquipment $equipment;

    #[ORM\ManyToOne(targetEntity: Licensee::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Licensee $borrower;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $returnDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->startDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipment(): ClubEquipment
    {
        return $this->equipment;
    }

    public function setEquipment(ClubEquipment $equipment): self
    {
        $this->equipment = $equipment;

        return $this;
    }

    public function getBorrower(): Licensee
    {
        return $this->borrower;
    }

    public function setBorrower(Licensee $borrower): self
    {
        $this->borrower = $borrower;

        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getReturnDate(): ?\DateTimeImmutable
    {
        return $this->returnDate;
    }

    public function setReturnDate(?\DateTimeImmutable $returnDate): self
    {
        $this->returnDate = $returnDate;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function isActive(): bool
    {
        return !$this->returnDate instanceof \DateTimeImmutable;
    }

    public function getLoanDuration(): ?\DateInterval
    {
        if (!$this->returnDate instanceof \DateTimeImmutable) {
            return $this->startDate->diff(new \DateTimeImmutable());
        }

        return $this->startDate->diff($this->returnDate);
    }

    public function getLoanDurationInDays(): ?int
    {
        $duration = $this->getLoanDuration();

        return $duration instanceof \DateInterval ? (int) $duration->format('%a') : null;
    }
}
