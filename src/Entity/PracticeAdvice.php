<?php

namespace App\Entity;

use App\Repository\PracticeAdviceRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PracticeAdviceRepository::class)]
class PracticeAdvice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[
        ORM\ManyToOne(
            targetEntity: Licensee::class,
            inversedBy: "practiceAdvices"
        )
    ]
    #[ORM\JoinColumn(nullable: false)]
    private Licensee $licensee;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $advice;

    #[
        ORM\ManyToOne(
            targetEntity: Licensee::class,
            inversedBy: "givenPracticeAdvices"
        )
    ]
    #[ORM\JoinColumn(nullable: false)]
    private Licensee $author;

    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?DateTimeImmutable $archivedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLicensee(): ?Licensee
    {
        return $this->licensee;
    }

    public function setLicensee(?Licensee $licensee): self
    {
        $this->licensee = $licensee;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAdvice(): ?string
    {
        return $this->advice;
    }

    public function setAdvice(string $advice): self
    {
        $this->advice = $advice;

        return $this;
    }

    public function getAuthor(): ?Licensee
    {
        return $this->author;
    }

    public function setAuthor(?Licensee $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getArchivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?DateTimeImmutable $archivedAt): self
    {
        $this->archivedAt = $archivedAt;

        return $this;
    }
}
