<?php

namespace App\Entity;

use App\DBAL\Types\EventParticipationStateType;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class TrainingEvent extends Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    public function getParticipations(): Collection
    {
        $participations = $this->participations;
        foreach ($this->getAssignedGroups() as $assignedGroup) {
            foreach ($assignedGroup->getLicensees() as $licensee) {
                if ($this->participations->exists(fn (int $key, EventParticipation $element) => $element->getParticipant() === $licensee)) {
                    continue;
                }
                $participations[] = (new EventParticipation())
                    ->setEvent($this)
                    ->setParticipant($licensee)
                    ->setActivity($this->getDiscipline())
                    ->setParticipationState(EventParticipationStateType::REGISTERED);
            }
        }

        return $participations;
    }
}
