<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContestEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContestEventRepository::class)]
class HobbyContestEvent extends ContestEvent
{
}
