<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sight_adjustments')]
#[ApiResource]
#[ApiResource(
    uriTemplate: '/bows/{bowId}/sight-adjustments/{id}',
    operations: [ new Get() ],
    uriVariables: [
        'bowId' => new Link(toProperty: 'bow', fromClass: Bow::class),
        'id' => new Link(fromClass: SightAdjustment::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/bows/{bowId}/sight-adjustments',
    operations: [ new GetCollection() ],
    uriVariables: [
        'bowId' => new Link(toProperty: 'bow', fromClass: Bow::class),
    ]
)]
class SightAdjustment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Bow::class, inversedBy: 'sightAdjustments')]
    #[ORM\JoinColumn(nullable: false)]
    public Bow $bow;

    #[ORM\Column(type: Types::INTEGER)]
    public int $distance = 0;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $setting = '';

    public function __construct()
    {
        $this->bow = new Bow();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
