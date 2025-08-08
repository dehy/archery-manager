<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\State\ApplicantProvider;
use App\State\ApplicantProcessor;
use App\Type\PracticeLevelType;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\ApplicantRepository::class)]
#[ORM\Table(name: 'applicants')]
#[Auditable]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['applicant:list']]
        ),
        new Get(
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['applicant:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['applicant:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['applicant:write']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    provider: ApplicantProvider::class,
    processor: ApplicantProcessor::class
)]
class Applicant implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: Types::INTEGER)]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Email]
    #[Assert\NotBlank]
    #[ApiProperty(types: ['https://schema.org/email'])]
    public string $email = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[ApiProperty(types: ['https://schema.org/familyName'])]
    public string $familyName = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[ApiProperty(types: ['https://schema.org/givenName'])]
    public string $givenName = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\LessThanOrEqual('-8 years')]
    #[ApiProperty(types: ['https://schema.org/birthDate'])]
    public \DateTimeImmutable $birthDate;

    #[ORM\Column(type: Types::STRING, enumType: PracticeLevelType::class, nullable: true)]
    public ?PracticeLevelType $practiceLevel = null;

    #[ORM\Column(type: Types::STRING, length: 7, nullable: true)]
    public ?string $licenseNumber = null;

    #[ORM\Column(type: Types::STRING, length: 12, nullable: true)]
    #[ApiProperty(types: ['https://schema.org/telephone'])]
    public ?string $phoneNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $comment = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    public int $season = 0;

    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $renewal = false;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    public ?string $licenseType = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $onWaitingList = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $docsRetrieved = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $paid = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $licenseCreated = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $paymentObservations = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->birthDate = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[\Override]
    public function __toString(): string
    {
        return sprintf('%s %s', $this->givenName, $this->familyName);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->givenName, $this->familyName);
    }
}
