<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\ORM\Mapping as ORM;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: ClubRepository::class)]
class Club implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 7, unique: true)]
    private ?string $fftaCode = null;

    #[ORM\Column(length: 7)]
    private ?string $primaryColor = null;

    #[Vich\UploadableField(mapping: 'clubs.logos', fileNameProperty: 'logoName')]
    private ?File $logo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\Column(length: 255, nullable: true)]
    #[Encrypted]
    private ?string $fftaUsername = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Encrypted]
    private ?string $fftaPassword = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%s - %s', $this->getCity(), $this->getName());
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     */
    public function setLogo(?File $logo = null): void
    {
        $this->logo = $logo;

        if ($logo instanceof File) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getLogo(): ?File
    {
        return $this->logo;
    }

    public function getLogoName(): ?string
    {
        return $this->logoName;
    }

    public function setLogoName(?string $logoName): self
    {
        $this->logoName = $logoName;

        return $this;
    }

    public function getPrimaryColor(): ?string
    {
        return $this->primaryColor;
    }

    public function setPrimaryColor(string $primaryColor): self
    {
        $this->primaryColor = $primaryColor;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getFftaCode(): ?string
    {
        return $this->fftaCode;
    }

    public function setFftaCode(string $fftaCode): self
    {
        $this->fftaCode = $fftaCode;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFftaUsername(): ?string
    {
        return $this->fftaUsername;
    }

    public function setFftaUsername(?string $fftaUsername): self
    {
        $this->fftaUsername = $fftaUsername;

        return $this;
    }

    public function getFftaPassword(): ?string
    {
        return $this->fftaPassword;
    }

    public function setFftaPassword(?string $fftaPassword): self
    {
        $this->fftaPassword = $fftaPassword;

        return $this;
    }

    public function generateLogoName(): string
    {
        return strtolower((new AsciiSlugger())->slug($this->name)->toString());
    }
}
