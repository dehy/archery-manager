<?php

namespace App\Entity;

use App\DBAL\Types\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class TrainingEvent extends Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function __toString(): string
    {
        return sprintf(
            '%s - %s - %s',
            $this->getStartsAt()->format('d/m/Y'),
            EventType::getReadableValue(self::class),
            $this->getName(),
        );
    }
}
