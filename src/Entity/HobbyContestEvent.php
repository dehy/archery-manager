<?php

namespace App\Entity;

use App\DBAL\Types\EventType;
use App\Repository\ContestEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContestEventRepository::class)]
class HobbyContestEvent extends ContestEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
}
