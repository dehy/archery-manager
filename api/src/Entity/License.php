<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Tool\ObjectComparator;
use App\Helper\SyncReturnValues;
use App\Type\LicenseActivityType;
use App\Type\LicenseAgeCategoryType;
use App\Type\LicenseCategoryType;
use App\Type\LicenseType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\Table(name: 'licenses')]
#[
    UniqueEntity(
        fields: ['licensee', 'season'],
        message: 'There is already an license for this season for this licensee',
    ),
]
#[ApiResource]
class License
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    private ?int $id = null;

    #[ORM\Column]
    public ?int $season = null;

    #[ORM\Column(enumType: LicenseType::class)]
    public LicenseType $type;

    #[ORM\Column(nullable: true, enumType: LicenseCategoryType::class)]
    public LicenseCategoryType $category;

    #[ORM\Column(nullable: true, enumType: LicenseAgeCategoryType::class)]
    public LicenseAgeCategoryType $ageCategory;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'licenses')]
    #[ORM\JoinColumn(nullable: false)]
    public ?Licensee $licensee = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, enumType: LicenseActivityType::class)]
    public array $activities = [];

    #[ORM\ManyToOne(inversedBy: 'licenses')]
    #[ORM\JoinColumn(nullable: false)]
    public ?Club $club = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function mergeWith(self $license): SyncReturnValues
    {
    $syncResult = ObjectComparator::equal($this, $license) ? SyncReturnValues::UNTOUCHED : SyncReturnValues::UPDATED;

        $this->activities = $license->activities;
        $this->ageCategory = $license->ageCategory;
        $this->category = $license->category;
        $this->season = $license->season;
        $this->type = $license->type;

        return $syncResult;
    }
}
