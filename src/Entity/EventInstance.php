<?php

namespace App\Entity;

class EventInstance implements \Stringable
{
    public Event $event;
    public \DateTimeInterface $instanceDate;

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

    public function getInstanceDate(): \DateTimeInterface
    {
        return $this->instanceDate;
    }

    public function setInstanceDate(\DateTimeInterface $instanceDate): self
    {
        $this->instanceDate = $instanceDate;

        return $this;
    }
}
