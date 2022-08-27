<?php

namespace App\Entity;

use App\DBAL\Types\LicenseType;
use App\DBAL\Types\PracticeLevelType;
use App\Repository\ApplicantRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicantRepository::class)]
class Applicant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank]
    private string $lastname;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank]
    private string $firstname;

    #[ORM\Column(type: "date_immutable")]
    #[Assert\LessThanOrEqual("2022/09/24 -8 years")]
    private DateTimeImmutable $birthdate;

    #[ORM\Column(type: "PracticeLevelType", nullable: true)]
    #[DoctrineAssert\EnumType(entity: PracticeLevelType::class)]
    private string $practiceLevel;

    #[ORM\Column(type: "string", length: 7, nullable: true)]
    private ?string $licenseNumber = null;

    #[ORM\Column(type: "string", length: 12, nullable: true)]
    private ?string $phoneNumber;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $registeredAt;

    #[ORM\Column(type: "integer")]
    private int $season = 2023;

    #[ORM\Column(type: "boolean")]
    private bool $renewal = false;

    #[ORM\Column(type: "string", length: 32, nullable: true)]
    private string $licenseType;

    #[ORM\Column]
    private bool $onWaitingList = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getBirthdate(): ?DateTimeImmutable
    {
        return $this->birthdate;
    }

    public function getAge(): ?int
    {
        return $this->birthdate->diff(new DateTimeImmutable())->y;
    }

    public function setBirthdate(DateTimeImmutable $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getPracticeLevel(): ?string
    {
        return $this->practiceLevel;
    }

    public function setPracticeLevel(?string $practiceLevel): self
    {
        $this->practiceLevel = $practiceLevel;

        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(?string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getRegisteredAt(): ?DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(DateTimeImmutable $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getSeason(): ?int
    {
        return $this->season;
    }

    public function setSeason(int $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function isRenewal(): bool
    {
        return $this->renewal;
    }

    public function setRenewal(bool $renewal): self
    {
        $this->renewal = $renewal;

        return $this;
    }

    public function getLicenseType(): string
    {
        return $this->licenseType;
    }

    public function setLicenseType(string $licenseType): self
    {
        $this->licenseType = $licenseType;

        return $this;
    }

    public function isOnWaitingList(): ?bool
    {
        return $this->onWaitingList;
    }

    public function setOnWaitingList(bool $onWaitingList): self
    {
        $this->onWaitingList = $onWaitingList;

        return $this;
    }
}
