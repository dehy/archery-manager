<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\BowProcessor;
use App\State\BowProvider;
use App\Type\BowType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\BowRepository::class)]
#[ORM\Table(name: 'bows')]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['bow:list']]
        ),
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['bow:read']]
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['bow:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_USER') and object.owner == user.licensee",
            denormalizationContext: ['groups' => ['bow:write']]
        ),
        new Delete(security: "is_granted('ROLE_USER') and object.owner == user.licensee"),
    ],
    provider: BowProvider::class,
    processor: BowProcessor::class
)]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/bows/{id}',
    operations: [new Get()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'owner', fromClass: Licensee::class),
        'id' => new Link(fromClass: Bow::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/bows',
    operations: [new GetCollection()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'owner', fromClass: Licensee::class),
    ]
)]
class Bow
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'bows')]
    #[ORM\JoinColumn(nullable: false)]
    public Licensee $owner;

    #[ORM\Column(type: Types::STRING, enumType: BowType::class)]
    public BowType $type = BowType::Recurve;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $brand = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $model = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $limbSize = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $limbStrength = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    public ?float $braceHeight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $drawLength = null;

    #[ORM\OneToMany(mappedBy: 'bow', targetEntity: SightAdjustment::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $sightAdjustments;

    public function __construct()
    {
        $this->owner = new Licensee();
        $this->sightAdjustments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
