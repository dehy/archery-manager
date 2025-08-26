<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Helper\SyncReturnValues;
use App\State\LicenseeProcessor;
use App\State\LicenseeProvider;
use App\Tool\ObjectComparator;
use App\Type\GenderType;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[Auditable]
#[ORM\Entity(repositoryClass: \App\Repository\LicenseeRepository::class)]
#[ORM\Table(name: 'licensees')]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['licensee:list']],
            filters: ['licensee.search_filter', 'licensee.order_filter']
        ),
        new Get(normalizationContext: ['groups' => ['licensee:read']]),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['licensee:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['licensee:write']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    provider: LicenseeProvider::class,
    processor: LicenseeProcessor::class
)]
#[ApiFilter(SearchFilter::class, properties: ['user' => 'exact', 'familyName' => 'partial', 'givenName' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['familyName', 'givenName', 'birthDate'])]
#[ApiResource(
    uriTemplate: '/users/{userId}/licensees/{id}',
    operations: [new Get()],
    uriVariables: [
        'userId' => new Link(toProperty: 'user', fromClass: User::class),
        'id' => new Link(fromClass: Licensee::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/users/{userId}/licensees',
    operations: [new GetCollection()],
    uriVariables: [
        'userId' => new Link(toProperty: 'user', fromClass: User::class),
    ]
)]
class Licensee implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'licensees')]
    #[ORM\JoinColumn]
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[ApiProperty(types: 'https://schema.org/person')]
    public User $user;

    #[ORM\Column(type: Types::STRING, enumType: GenderType::class)]
    public GenderType $gender = GenderType::Other;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $familyName = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $givenName = '';

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    public \DateTimeInterface $birthDate;

    #[ORM\Column(type: Types::STRING, length: 8, unique: true, nullable: true)]
    #[Assert\Length(min: 8, max: 8)]
    public ?string $fftaMemberCode = null;

    #[ORM\Column(type: Types::INTEGER, unique: true, nullable: true)]
    public ?int $fftaId = null;

    #[ORM\OneToMany(mappedBy: 'licensee', targetEntity: License::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $licenses;

    #[ORM\OneToMany(mappedBy: 'participant', targetEntity: EventParticipation::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $eventParticipations;

    #[ORM\OneToMany(mappedBy: 'licensee', targetEntity: Result::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $results;

    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'licensees')]
    public Collection $groups;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    public ?\DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->user = new User();
        $this->birthDate = new \DateTime();
        $this->licenses = new ArrayCollection();
        $this->eventParticipations = new ArrayCollection();
        $this->results = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getFullname();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLicenses(Collection $licenses): self
    {
        $this->licenses = $licenses;
        foreach ($licenses as $license) {
            $license->licensee = $this;
        }

        return $this;
    }

    public function addLicense(License $license): self
    {
        if (!$this->licenses->contains($license)) {
            $this->licenses[] = $license;
        }
        $license->licensee = $this;

        return $this;
    }

    public function removeLicense(License $license): self
    {
        if ($this->licenses->contains($license)) {
            $this->licenses->removeElement($license);
        }
        $license->licensee = null;

        return $this;
    }

    public function getFullname(): string
    {
        return \sprintf('%s %s', $this->givenName, $this->familyName);
    }

    public function getGivenNameWithInitial(): string
    {
        return \sprintf('%s %s.', $this->givenName, strtoupper(substr((string) $this->familyName, 0, 1)));
    }

    public function mergeWith(self $licensee): SyncReturnValues
    {
        $syncResult = ObjectComparator::equal($this, $licensee) ? SyncReturnValues::UNTOUCHED : SyncReturnValues::UPDATED;

        $this->gender = $licensee->gender;
        $this->familyName = $licensee->familyName;
        $this->givenName = $licensee->givenName;
        $this->birthDate = $licensee->birthDate;
        $this->fftaMemberCode = $licensee->fftaMemberCode;
        $this->fftaId = $licensee->fftaId;

        return $syncResult;
    }
}
