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
use App\State\ArrowProcessor;
use App\State\ArrowProvider;
use App\Type\ArrowType;
use App\Type\FletchingType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\ArrowRepository::class)]
#[ORM\Table(name: 'arrows')]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['arrow:list']]
        ),
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['arrow:read']]
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['arrow:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_USER') and object.owner == user.licensee",
            denormalizationContext: ['groups' => ['arrow:write']]
        ),
        new Delete(security: "is_granted('ROLE_USER') and object.owner == user.licensee"),
    ],
    provider: ArrowProvider::class,
    processor: ArrowProcessor::class
)]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/arrows/{id}',
    operations: [new Get()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'owner', fromClass: Licensee::class),
        'id' => new Link(fromClass: Arrow::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/arrows',
    operations: [new GetCollection()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'owner', fromClass: Licensee::class),
    ]
)]
class Arrow
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'arrows')]
    #[ORM\JoinColumn(nullable: false)]
    public Licensee $owner;

    #[ORM\Column(type: Types::STRING, enumType: ArrowType::class)]
    public ArrowType $type = ArrowType::Carbon;

    #[ORM\Column(type: Types::INTEGER)]
    public int $spine = 0;

    #[ORM\Column(type: Types::STRING, enumType: FletchingType::class)]
    public FletchingType $fletching = FletchingType::Plastic;

    public function __construct()
    {
        $this->owner = new Licensee();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
