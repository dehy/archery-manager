<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

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

    #[Vich\UploadableField(mapping: 'clubs_logos', fileNameProperty: 'logoName')]
    private ?File $logo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoName = null;

    #[ORM\Column(length: 7)]
    private ?string $primaryColor = null;

    #[ORM\Column(length: 255)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 8, unique: true)]
    private ?string $fftaCode = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'club')]
    private Collection $events;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'club')]
    private Collection $groups;

    /**
     * @var Collection<int, License>
     */
    #[ORM\OneToMany(targetEntity: License::class, mappedBy: 'club')]
    private Collection $licenses;

    #[ORM\Column(length: 255, nullable: true)]
    #[Encrypted]
    private ?string $fftaUsername = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Encrypted]
    private ?string $fftaPassword = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $departmentCode = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $regionCode = null;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON)]
    private array $watchedDepartmentCodes = [];

    /**
     * @var list<string>
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON)]
    private array $watchedRegionCodes = [];

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->licenses = new ArrayCollection();
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

    /**
     * @return Collection<int, License>
     */
    public function getLicenses(): Collection
    {
        return $this->licenses;
    }

    public function addLicense(License $license): self
    {
        if (!$this->licenses->contains($license)) {
            $this->licenses->add($license);
            $license->setClub($this);
        }

        return $this;
    }

    public function removeLicense(License $license): self
    {
        // set the owning side to null (unless already changed)
        if ($this->licenses->removeElement($license) && $license->getClub() === $this) {
            $license->setClub(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): ?Collection
    {
        return $this->events;
    }

    public function setEvents(?Collection $events): self
    {
        $this->events = $events;

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): ?Collection
    {
        return $this->groups;
    }

    public function setGroups(?Collection $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    public function generateLogoName(): string
    {
        return strtolower(new AsciiSlugger()->slug($this->getName())->toString());
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

    public function getDepartmentCode(): ?string
    {
        return $this->departmentCode;
    }

    public function setDepartmentCode(?string $departmentCode): self
    {
        $this->departmentCode = $departmentCode;

        return $this;
    }

    public function getRegionCode(): ?string
    {
        return $this->regionCode;
    }

    public function setRegionCode(?string $regionCode): self
    {
        $this->regionCode = $regionCode;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getWatchedDepartmentCodes(): array
    {
        return $this->watchedDepartmentCodes;
    }

    /**
     * @param list<string> $watchedDepartmentCodes
     */
    public function setWatchedDepartmentCodes(array $watchedDepartmentCodes): self
    {
        $this->watchedDepartmentCodes = $watchedDepartmentCodes;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getWatchedRegionCodes(): array
    {
        return $this->watchedRegionCodes;
    }

    /**
     * @param list<string> $watchedRegionCodes
     */
    public function setWatchedRegionCodes(array $watchedRegionCodes): self
    {
        $this->watchedRegionCodes = $watchedRegionCodes;

        return $this;
    }
}
