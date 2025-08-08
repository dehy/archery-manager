<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Event;
use App\Repository\EventRepository;

/**
 * @implements ProviderInterface<Event>
 */
final readonly class EventProvider implements ProviderInterface
{
    public function __construct(
        private EventRepository $eventRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof \ApiPlatform\Metadata\GetCollection) {
            return $this->eventRepository->findAll();
        }

        if ($operation instanceof \ApiPlatform\Metadata\Get) {
            return $this->eventRepository->find($uriVariables['id']);
        }

        return null;
    }
}
