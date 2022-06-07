<?php

namespace App\Entity;

use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\Repository\LicenseRepository;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: LicenseRepository::class)]
#[
    UniqueEntity(
        fields: ["licensee", "season"],
        message: "There is already an license for this season for this licensee"
    )
]
class License
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "integer")]
    private $season;

    #[ORM\Column(type: "LicenseType")]
    #[DoctrineAssert\EnumType(entity: LicenseType::class)]
    private $type;

    #[ORM\Column(type: "LicenseCategoryType", nullable: true)]
    #[DoctrineAssert\EnumType(entity: LicenseCategoryType::class)]
    private $category;

    #[ORM\Column(type: "LicenseAgeCategoryType", nullable: true)]
    #[DoctrineAssert\EnumType(entity: LicenseAgeCategoryType::class)]
    private $ageCategory;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: "licenses")]
    #[ORM\JoinColumn(nullable: false)]
    private $licensee;

    #[ORM\Column(type: "simple_array")]
    private $activities = [];

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

    public function merge(License $license): void
    {
        $this->setType($license->getType());
        $this->setSeason($license->getSeason());
        $this->setCategory($license->getCategory());
        $this->setAgeCategory($license->getAgeCategory());
        $this->setActivities($license->getActivities());
    }
}
