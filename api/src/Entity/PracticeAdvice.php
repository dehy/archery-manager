<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'practice_advices')]
#[Auditable]
#[ApiResource]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/practice-advices/{id}',
    operations: [new Get()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'licensee', fromClass: Licensee::class),
        'id' => new Link(fromClass: PracticeAdvice::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/practice-advices',
    operations: [new GetCollection()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'licensee', fromClass: Licensee::class),
    ]
)]
class PracticeAdvice
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'practiceAdvices')]
    #[ORM\JoinColumn(nullable: false)]
    public Licensee $licensee;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[ApiProperty(types: ['https://schema.org/name'])]
    public string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[ApiProperty(types: ['https://schema.org/text'])]
    public string $advice = '';

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'givenPracticeAdvices')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(types: ['https://schema.org/author'])]
    public Licensee $author;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[ApiProperty(types: ['https://schema.org/dateCreated'])]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $archivedAt = null;

    public function __construct()
    {
        $this->licensee = new Licensee();
        $this->author = new Licensee();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
