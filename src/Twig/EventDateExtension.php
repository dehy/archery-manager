<?php

namespace App\Twig;

use App\Entity\Event;
use App\Entity\EventInstance;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\TwigFilter;

class EventDateExtension extends AbstractExtension
{
    public function __construct(
        private readonly IntlExtension $intlExtension,
        private readonly DateTimeFormatter $dateTimeFormatter
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
        EventInstance $eventInstance,
        bool $includeTime = true,
        bool $diff = false
    ): string {
        $event = $eventInstance->getEvent();
        if (1 === $event->getEndDate()->diff($event->getStartDate())->days) {
            $date = sprintf(
                'les %s et %s',
                $eventInstance->getInstanceDate()->format('j'),
                $this->formatDate($environment, $eventInstance->getInstanceDate()->modify('+1 day'))
            );
        } elseif ($event->getStartDate()->format('d-m-Y') !== $event->getEndDate()->format('d-m-Y')) {
            $date = sprintf(
                'du %s au %s',
                $eventInstance->getInstanceDate()->format('j'),
                $this->formatDate($environment, $event->getEndDate())
            );
        } else {
            if ($includeTime && !$event->isFullDayEvent()) {
                $date = sprintf(
                    'le %s de %s à %s',
                    $this->formatDate($environment, $eventInstance->getInstanceDate()),
                    $event->getStartTime()->format('H:i'),
                    $event->getEndTime()->format('H:i'),
                );
            } else {
                $date = sprintf(
                    'le %s',
                    $this->formatDate($environment, $eventInstance->getInstanceDate()),
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

        return $this->dateTimeFormatter->formatDiff($date, $now, 'fr');
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
