<?php

namespace App\Entity;

use App\DBAL\Types\PracticeLevelType;
use App\Repository\ApplicantRepository;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicantRepository::class)]
#[Auditable]
class Applicant implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $lastname;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private string $firstname;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATE_IMMUTABLE)]
    #[Assert\LessThanOrEqual('2022/09/24 -8 years')]
    private \DateTimeImmutable $birthdate;

    #[ORM\Column(type: 'PracticeLevelType', nullable: true)]
    #[DoctrineAssert\EnumType(entity: PracticeLevelType::class)]
    private string $practiceLevel;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 7, nullable: true)]
    private ?string $licenseNumber = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 12, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $registeredAt;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private int $season = 2023;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $renewal = false;

    #[ORM\Column]
    private bool $tournament = false;

    #[ORM\Column]
    private bool $onWaitingList = false;

    #[ORM\Column]
    private bool $docsRetrieved = false;

    #[ORM\Column]
    private bool $paid = false;

    #[ORM\Column]
    private bool $licenseCreated = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentObservations = null;

    public function __toString(): string
    {
        return $this->getCompleteName();
    }

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

    public function getBirthdate(): ?\DateTimeImmutable
    {
        return $this->birthdate;
    }

    public function getAge(): ?int
    {
        return $this->birthdate->diff(new \DateTimeImmutable())->y;
    }

    public function setBirthdate(\DateTimeImmutable $birthdate): self
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

    public function getRegisteredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeImmutable $registeredAt): self
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

    public function getTournament(): bool
    {
        return $this->tournament;
    }

    public function setTournament(bool $tournament): self
    {
        $this->tournament = $tournament;

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

    public function isDocsRetrieved(): ?bool
    {
        return $this->docsRetrieved;
    }

    public function setDocsRetrieved(bool $docsRetrieved): self
    {
        $this->docsRetrieved = $docsRetrieved;

        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): self
    {
        $this->paid = $paid;

        return $this;
    }

    public function isLicenseCreated(): ?bool
    {
        return $this->licenseCreated;
    }

    public function setLicenseCreated(bool $licenseCreated): self
    {
        $this->licenseCreated = $licenseCreated;

        return $this;
    }

    public function getCompleteName(): string
    {
        return sprintf(
            '%s %s',
            mb_strtoupper($this->getLastname()),
            ucfirst($this->getFirstname()),
        );
    }

    public function getPaymentObservations(): ?string
    {
        return $this->paymentObservations;
    }

    public function setPaymentObservations(?string $paymentObservations): self
    {
        $this->paymentObservations = $paymentObservations;

        return $this;
    }
}
