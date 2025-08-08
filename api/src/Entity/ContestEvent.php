<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Type\ContestType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource]
class ContestEvent extends Event
{
    #[ORM\Column(type: Types::STRING, enumType: ContestType::class, nullable: true)]
    public ?ContestType $contestType = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Result::class, cascade: ['persist'], orphanRemoval: true)]
    public Collection $results;

    public function __construct()
    {
        parent::__construct();
        $this->results = new ArrayCollection();
    }
}
