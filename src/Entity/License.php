<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Repository\LicenseRepository;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: LicenseRepository::class)]
#[
    UniqueEntity(
        fields: ['licensee', 'season'],
        message: 'There is already an license for this season for this licensee',
    ),
]
#[Auditable]
#[ApiResource]
class License
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $season = null;

    #[ORM\Column(type: 'LicenseType')]
    #[DoctrineAssert\EnumType(entity: LicenseType::class)]
    private $type;

    #[ORM\Column(type: 'LicenseCategoryType', nullable: true)]
    #[DoctrineAssert\EnumType(entity: LicenseCategoryType::class)]
    private $category;

    #[ORM\Column(type: 'LicenseAgeCategoryType', nullable: true)]
    #[DoctrineAssert\EnumType(entity: LicenseAgeCategoryType::class)]
    private $ageCategory;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'licenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Licensee $licensee = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::SIMPLE_ARRAY)]
    private array $activities = [];

    #[ORM\ManyToOne(inversedBy: 'licenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Club $club = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getAgeCategory()
    {
        return $this->ageCategory;
    }

    public function setAgeCategory($ageCategory): self
    {
        $this->ageCategory = $ageCategory;

        return $this;
    }

    public function getLicensee(): ?Licensee
    {
        return $this->licensee;
    }

    public function setLicensee(?Licensee $licensee): self
    {
        $this->licensee = $licensee;

        $licensee->addLicense($this);

        return $this;
    }

    public function getActivities(): ?array
    {
        return $this->activities;
    }

    public function setActivities(array $activities): self
    {
        $this->activities = $activities;

        return $this;
    }

    public function mergeWith(self $license): void
    {
        $this->setActivities($license->getActivities());
        $this->setAgeCategory($license->getAgeCategory());
        $this->setCategory($license->getCategory());
        $this->setSeason($license->getSeason());
        $this->setType($license->getType());
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): self
    {
        $this->club = $club;

        return $this;
    }
}
