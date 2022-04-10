<?php

namespace App\Entity;

use App\DBAL\Types\LicenseType;
use App\Repository\LicenseRepository;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;

#[ORM\Entity(repositoryClass: LicenseRepository::class)]
class License
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $season;

    #[ORM\Column(type: 'LicenseType')]
    #[DoctrineAssert\EnumType(entity: LicenseType::class)]
    private $type;

    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    private $number;

    #[ORM\Column(type: 'LicenseCategoryType', nullable: true)]
    private $category;

    #[ORM\Column(type: 'LicenseAgeCategoryType', nullable: true)]
    private $ageCategory;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'licenses')]
    #[ORM\JoinColumn(nullable: false)]
    private $owner;

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

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

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

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
