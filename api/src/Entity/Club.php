<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\ClubProcessor;
use App\State\ClubProvider;
use App\Type\SportType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['club:list']]),
        new Get(normalizationContext: ['groups' => ['club:read']]),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['club:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['club:write']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    provider: ClubProvider::class,
    processor: ClubProcessor::class
)]
#[ORM\Entity(repositoryClass: \App\Repository\ClubRepository::class)]
#[ORM\Table(name: 'clubs')]
class Club implements \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\Column(enumType: SportType::class)]
    #[Assert\NotBlank]
    public SportType $sport = SportType::Archery;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    public string $name = '';

    #[Embedded(class: PostalAddress::class, columnPrefix: 'address_')]
    public ?PostalAddress $address = null;

    public ?File $logo = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $logoName = null;

    #[ORM\Column(length: 7, nullable: true)]
    #[Assert\Length(7)]
    public ?string $primaryColor = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[ORM\Column(length: 7, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 7, max: 7)]
    public string $fftaCode = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Encrypted]
    public ?string $fftaUsername = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Encrypted]
    public ?string $fftaPassword = null;

    #[ORM\Column]
    public ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    public ?\DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'club', targetEntity: Group::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $groups;

    #[ORM\OneToMany(mappedBy: 'club', targetEntity: Event::class, cascade: ['persist'])]
    public Collection $events;

    #[ORM\OneToMany(mappedBy: 'club', targetEntity: License::class, cascade: ['persist'])]
    public Collection $licenses;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->groups = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->licenses = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return \sprintf('%s - %s', $this->address->locality, $this->name);
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function generateLogoName(): string
    {
        return strtolower((new AsciiSlugger())->slug($this->name)->toString());
    }
}
