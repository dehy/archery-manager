<?php

namespace App\Entity;

use App\DBAL\Types\EventKindType;
use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class TrainingEvent extends Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function __construct()
    {
        parent::__construct();
        $this->setKind(EventKindType::TRAINING);
    }
}
