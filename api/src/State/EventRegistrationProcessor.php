<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\EventRegistrationRequest;
use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\Licensee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<EventRegistrationRequest, EventParticipation>
 */
final readonly class EventRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof EventRegistrationRequest) {
            throw new BadRequestHttpException('Invalid data provided');
        }

        // Fetch the event
        $event = $this->entityManager->getRepository(Event::class)->find($data->eventId);
        if (!$event) {
            throw new NotFoundHttpException('Event not found');
        }

        // Fetch the participant
        $participant = $this->entityManager->getRepository(Licensee::class)->find($data->participantId);
        if (!$participant) {
            throw new NotFoundHttpException('Participant not found');
        }

        // Check if already registered
        $existingParticipation = $this->entityManager->getRepository(EventParticipation::class)
            ->findOneBy(['event' => $event, 'licensee' => $participant]);

        if ($existingParticipation) {
            throw new BadRequestHttpException('Participant already registered for this event');
        }

        // Create new participation
        $participation = new EventParticipation();
        $participation->event = $event;
        $participation->licensee = $participant;
        $participation->comment = $data->comment;
        $participation->registrationDate = new \DateTimeImmutable();

        // Business logic: validate event capacity, prerequisites, etc.
        if ($event->getMaxParticipants() && count($event->participations) >= $event->getMaxParticipants()) {
            throw new BadRequestHttpException('Event is full');
        }

        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        return $participation;
    }
}
