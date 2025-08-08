<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource]
class FreeTrainingEvent extends Event
{
    // Free training events don't need additional properties beyond the base Event class
}
