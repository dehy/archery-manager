<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;

#[Embeddable]
class PostalAddress
{
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $country = null;

    #[Column(type: Types::STRING, nullable: true)]
    public ?string $locality = null;

    #[Column(type: Types::STRING, nullable: true)]
    public ?string $postalCode = null;

    #[Column(type: Types::STRING, nullable: true)]
    public ?string $address = null;

    public function __toString(): string
    {
        return sprintf('%s\n%s %s\n%s', $this->address, $this->postalCode, $this->locality, $this->country);
    }
}
