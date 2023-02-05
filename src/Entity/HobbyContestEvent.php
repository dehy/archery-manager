<?php

namespace App\Entity;

use App\DBAL\Types\EventKindType;
use App\Repository\ContestEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContestEventRepository::class)]
class HobbyContestEvent extends ContestEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function __construct()
    {
        parent::__construct();
        $this->setKind(EventKindType::CONTEST_HOBBY);
    }
}
