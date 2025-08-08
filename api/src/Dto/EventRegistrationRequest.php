<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Entity\Event;
use App\Entity\Licensee;
use App\Type\DisciplineType;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/events/{eventId}/register',
            denormalizationContext: ['groups' => ['event_registration:write']],
            normalizationContext: ['groups' => ['event_registration:read']],
            security: "is_granted('ROLE_USER')"
        )
    ],
    messenger: true
)]
final class EventRegistrationRequest
{
    #[ApiProperty(types: ['https://schema.org/Event'])]
    public int $eventId;

    #[ApiProperty(types: ['https://schema.org/Person'])]
    public int $participantId;

    #[ApiProperty(types: ['https://schema.org/Text'])]
    public ?string $comment = null;

    #[ApiProperty(types: ['https://schema.org/Boolean'])]
    public bool $needsEquipment = false;

    #[ApiProperty(types: ['https://schema.org/Text'])]
    public ?DisciplineType $preferredDiscipline = null;

    public function __construct(
        int $eventId,
        int $participantId,
        ?string $comment = null,
        bool $needsEquipment = false,
        ?DisciplineType $preferredDiscipline = null
    ) {
        $this->eventId = $eventId;
        $this->participantId = $participantId;
        $this->comment = $comment;
        $this->needsEquipment = $needsEquipment;
        $this->preferredDiscipline = $preferredDiscipline;
    }
}
