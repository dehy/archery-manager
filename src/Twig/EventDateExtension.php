<?php

namespace App\Twig;

use App\Entity\Event;
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
        Event $event,
        bool $includeTime = true,
        bool $diff = false
    ): string {
        if (1 === $event->getEndTime()->diff($event->getStartTime())->days) {
            $date = sprintf(
                'les %s et %s',
                $event->getStartTime()->format('j'),
                $this->formatDate($environment, $event->getEndTime())
            );
        } elseif ($event->getStartTime()->format('d-m-Y') !== $event->getEndTime()->format('d-m-Y')) {
            $date = sprintf(
                'du %s au %s',
                $event->getStartTime()->format('j'),
                $this->formatDate($environment, $event->getEndTime())
            );
        } else {
            if ($includeTime && !$event->isFullDayEvent()) {
                $date = sprintf(
                    'le %s de %s à %s',
                    $this->formatDate($environment, $event->getStartTime()),
                    $event->getStartTime()->format('H:i'),
                    $event->getEndTime()->format('H:i'),
                );
            } else {
                $date = sprintf(
                    'le %s',
                    $this->formatDate($environment, $event->getStartTime()),
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
        if ($event->getStartTime() > $now) {
            $date = $event->getStartTime();
        } elseif ($event->getEndTime() < $now) {
            $date = $event->getEndTime();
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
