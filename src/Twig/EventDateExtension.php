<?php

namespace App\Twig;

use App\Entity\Event;
use Knp\Bundle\TimeBundle\Twig\Extension\TimeExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\TwigFilter;

class EventDateExtension extends AbstractExtension
{
    public function __construct(
        private readonly IntlExtension $intlExtension,
        private readonly TimeExtension $timeExtension
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('event_date', $this->eventDate(...), ['needs_environment' => true]),
        ];
    }

    public function eventDate(
        Environment $environment,
        Event $event,
        bool $includeTime = true,
        bool $diff = false
    ): string {
        if (1 === $event->getEndsAt()->diff($event->getStartsAt())->days) {
            $date = sprintf(
                'les %s et %s',
                $event->getStartsAt()->format('j'),
                $this->formatDate($environment, $event->getEndsAt())
            );
        } elseif ($event->getStartsAt()->format('d-m-Y') !== $event->getEndsAt()->format('d-m-Y')) {
            $date = sprintf(
                'du %s au %s',
                $event->getStartsAt()->format('j'),
                $this->formatDate($environment, $event->getEndsAt())
            );
        } else {
            if ($includeTime && !$event->isAllDay()) {
                $date = sprintf(
                    'le %s de %s à %s',
                    $this->formatDate($environment, $event->getStartsAt()),
                    $event->getStartsAt()->format('H:i'),
                    $event->getEndsAt()->format('H:i'),
                );
            } else {
                $date = sprintf(
                    'le %s',
                    $this->formatDate($environment, $event->getStartsAt()),
                );
            }
        }

        if (true === $diff) {
            $date = sprintf(
                '%s (%s)',
                $date,
                $this->diff($event)
            );
        }

        return $date;
    }

    private function diff(Event $event): string
    {
        $now = new \DateTime();
        if ($event->getStartsAt() > $now) {
            $date = $event->getStartsAt();
        } elseif ($event->getEndsAt() < $now) {
            $date = $event->getEndsAt();
        } else {
            return 'en ce moment';
        }
        return  $this->timeExtension->diff($date, $now, 'fr');
    }

    private function formatDate(Environment $environment, \DateTimeInterface $datetime): string
    {
        return $this->intlExtension->formatDate(
            $environment,
            $datetime,
            'medium',
            '',
            null,
            'gregorian',
            'fr'
        );
    }
}
