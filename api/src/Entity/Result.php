<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Type\DisciplineType;
use App\Type\LicenseActivityType;
use App\Type\LicenseAgeCategoryType;
use App\Type\TargetTypeType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'results')]
#[ApiResource]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/results/{id}',
    operations: [new Get()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'licensee', fromClass: Licensee::class),
        'id' => new Link(fromClass: Result::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/licensees/{licenseeId}/results',
    operations: [new GetCollection()],
    uriVariables: [
        'licenseeId' => new Link(toProperty: 'licensee', fromClass: Licensee::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/events/{eventId}/results/{id}',
    operations: [new Get()],
    uriVariables: [
        'eventId' => new Link(toProperty: 'event', fromClass: ContestEvent::class),
        'id' => new Link(fromClass: Result::class),
    ]
)]
#[ApiResource(
    uriTemplate: '/events/{eventId}/results',
    operations: [new GetCollection()],
    uriVariables: [
        'eventId' => new Link(toProperty: 'event', fromClass: ContestEvent::class),
    ]
)]
class Result
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    public Licensee $licensee;

    #[ORM\ManyToOne(targetEntity: ContestEvent::class, inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false)]
    public ContestEvent $event;

    #[ORM\Column(type: Types::STRING, enumType: DisciplineType::class)]
    public DisciplineType $discipline = DisciplineType::Target;

    #[ORM\Column(type: Types::STRING, enumType: LicenseAgeCategoryType::class)]
    public LicenseAgeCategoryType $ageCategory = LicenseAgeCategoryType::SENIOR;

    #[ORM\Column(type: Types::STRING, enumType: LicenseActivityType::class)]
    public LicenseActivityType $activity = LicenseActivityType::CL;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $distance = null;

    #[ORM\Column(type: Types::STRING, enumType: TargetTypeType::class)]
    public TargetTypeType $targetType = TargetTypeType::Monospot;

    #[ORM\Column(type: Types::INTEGER)]
    public int $targetSize = 122;

    #[ORM\Column(type: Types::INTEGER)]
    public int $total = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $score1 = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $score2 = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $nb10 = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $nb10p = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $position = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public ?int $fftaRanking = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->licensee = new Licensee();
        $this->event = new ContestEvent();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
