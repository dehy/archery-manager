<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\Event;
use App\Entity\Licensee;

final class EventRegistration
{
    #[ApiProperty(types: ['https://schema.org/Event'])]
    public Event $event;

    #[ApiProperty(types: ['https://schema.org/Person'])]
    public Licensee $participant;

    #[ApiProperty(types: ['https://schema.org/DateTime'])]
    public \DateTimeInterface $registrationDate;

    public ?string $comment = null;

    public function __construct(
        Event $event,
        Licensee $participant,
        ?\DateTimeInterface $registrationDate = null,
        ?string $comment = null,
    ) {
        $this->event = $event;
        $this->participant = $participant;
        $this->registrationDate = $registrationDate ?? new \DateTimeImmutable();
        $this->comment = $comment;
    }
}
