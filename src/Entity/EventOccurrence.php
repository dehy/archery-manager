<?php

namespace App\Entity;

class EventOccurrence implements \Stringable
{
    public Event $event;
    public \DateTimeInterface $occurrenceDate;

    public function __toString()
    {
        return $this->event->__toString();
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getOccurrenceDate(): \DateTimeInterface
    {
        return $this->occurrenceDate;
    }

    public function setOccurrenceDate(\DateTimeInterface $occurrenceDate): self
    {
        $this->occurrenceDate = $occurrenceDate;

        return $this;
    }
}
