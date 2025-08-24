<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'groups')]
#[ApiResource]
#[ApiResource(
    uriTemplate: '/clubs/{clubId}/groups/{id}',
    operations: [new Get()],
    uriVariables: [
        'clubId' => new Link(toProperty: 'club', fromClass: Club::class),
        'id' => new Link(fromClass: Group::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/clubs/{clubId}/groups',
    operations: [new GetCollection()],
    uriVariables: [
        'clubId' => new Link(toProperty: 'club', fromClass: Club::class),
    ]
)]
class Group implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'groups')]
    #[ORM\JoinColumn(nullable: false)]
    public Club $club;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[ApiProperty(types: ['https://schema.org/name'])]
    public string $name = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[ApiProperty(types: ['https://schema.org/description'])]
    public ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Licensee::class, inversedBy: 'groups')]
    #[ORM\JoinTable(name: 'group_licensees')]
    public Collection $licensees;

    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'assignedGroups')]
    public Collection $events;

    public function __construct()
    {
        $this->club = new Club();
        $this->licensees = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
