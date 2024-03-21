<?php

namespace App\Entity;

class EventInstance implements \Stringable
{
    public Event $event;
    public \DateTimeImmutable $instanceDate;

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

    public function getInstanceDate(): \DateTimeImmutable
    {
        return $this->instanceDate;
    }

    public function setInstanceDate(\DateTimeImmutable $instanceDate): self
    {
        $this->instanceDate = $instanceDate;

        return $this;
    }
}
