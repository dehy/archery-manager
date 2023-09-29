<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    #[Vich\UploadableField(mapping: 'clubs.logos', fileNameProperty: 'logoName')]
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
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Event>|\App\Entity\Event[]
     */
    #[ORM\OneToMany(mappedBy: 'club', targetEntity: Event::class)]
    private Collection $events;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Group>|\App\Entity\Group[]
     */
    #[ORM\OneToMany(mappedBy: 'club', targetEntity: Group::class)]
    private Collection $groups;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\License>|\App\Entity\License[]
     */
    #[ORM\OneToMany(mappedBy: 'club', targetEntity: License::class)]
    private Collection $licenses;

    #[ORM\Column(length: 255, nullable: true)]
    #[Encrypted]
    private ?string $fftaUsername = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Encrypted]
    private ?string $fftaPassword = null;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->licenses = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->getCity(), $this->getName());
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
    public function setLogo(File $logo = null): void
    {
        $this->logo = $logo;

        if (null !== $logo) {
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
        if ($this->licenses->removeElement($license)) {
            // set the owning side to null (unless already changed)
            if ($license->getClub() === $this) {
                $license->setClub(null);
            }
        }

        return $this;
    }

    public function getEvents(): ?Collection
    {
        return $this->events;
    }

    public function setEvents(?Collection $events): self
    {
        $this->events = $events;

        return $this;
    }

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
        return strtolower((new AsciiSlugger())->slug($this->getName()));
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
}
