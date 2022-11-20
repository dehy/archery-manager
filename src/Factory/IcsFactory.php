<?php

/**
 * This is free and unencumbered software released into the public domain.
 *
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a compiled
 * binary, for any purpose, commercial or non-commercial, and by any
 * means.
 *
 * In jurisdictions that recognize copyright laws, the author or authors
 * of this software dedicate any and all copyright interest in the
 * software to the public domain. We make this dedication for the benefit
 * of the public at large and to the detriment of our heirs and
 * successors. We intend this dedication to be an overt act of
 * relinquishment in perpetuity of all present and future rights to this
 * software under copyright law.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * For more information, please refer to <http://unlicense.org>
 *
 * ICS.php
 * =============================================================================
 * Use this class to create an .ics file.
 *
 *
 * Usage
 * -----------------------------------------------------------------------------
 * Basic usage - generate ics file contents (see below for available properties):
 *   $ics = new ICS($props);
 *   $ics_file_contents = $ics->to_string();
 *
 * Setting properties after instantiation
 *   $ics = new ICS();
 *   $ics->set('summary', 'My awesome event');
 *
 * You can also set multiple properties at the same time by using an array:
 *   $ics->set(array(
 *     'dtstart' => 'now + 30 minutes',
 *     'dtend' => 'now + 1 hour'
 *   ));
 *
 * Available properties
 * -----------------------------------------------------------------------------
 * description
 *   String description of the event.
 * dtend
 *   A date/time stamp designating the end of the event. You can use either a
 *   DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
 * dtstart
 *   A date/time stamp designating the start of the event. You can use either a
 *   DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
 * location
 *   String address or description of the location of the event.
 * summary
 *   String short summary of the event - usually used as the title.
 * url
 *   A url to attach to the the event. Make sure to add the protocol (http://
 *   or https://).
 */

namespace App\Factory;

class IcsFactory
{
    public const ALL_DAY_DT_FORMAT = 'Ymd';
    public const DT_FORMAT = 'Ymd\THis\Z';

    protected array $properties = [];
    private array $availableProperties = [
        'description',
        'dtend',
        'dtstart',
        'location',
        'summary',
        'url',
    ];
    private bool $isAllDay = false;

    public static function new(string $summary): IcsFactory
    {
        $factory = new self();
        $factory->setSummary($summary);

        return $factory;
    }

    public function setAllDay(bool $allDay): self
    {
        $this->isAllDay = $allDay;

        return $this;
    }

    public function setSummary(string $summary): self
    {
        $this->set('summary', $summary);

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->set('description', $description);

        return $this;
    }

    public function setStart(\DateTimeInterface $start): self
    {
        $this->set('dtstart', $start);

        return $this;
    }

    public function setEnd(\DateTimeInterface $end): self
    {
        $this->set('dtend', $end);

        return $this;
    }

    public function setLocation(string $location): self
    {
        $this->set('location', $location);

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->set('url', $url);

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function set(string $key, mixed $val): void
    {
        if (in_array($key, $this->availableProperties)) {
            $this->properties[$key] = $val;
        }
    }

    /**
     * @throws \Exception
     */
    public function getICS(): string
    {
        $rows = $this->buildProps();

        return implode("\r\n", $rows);
    }

    /**
     * @throws \Exception
     */
    private function buildProps(): array
    {
        // Build ICS properties - add header
        $icsProps = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
        ];

        // Build ICS properties - add header
        $props = [];
        foreach ($this->properties as $k => $v) {
            $realKey = match ($k) {
                'dtstart' => $this->isAllDay ? 'DTSTART;VALUE=DATE' : $k,
                'dtend' => $this->isAllDay ? 'DTEND;VALUE=DATE' : $k,
                'url' => 'URL;VALUE=URI',
                default => $k,
            };
            if (in_array($k, ['dtstart', 'dtend'])) {
                if ($this->isAllDay && 'dtend' === $k) {
                    $v = \DateTime::createFromInterface($v);
                    $v = $v->add(new \DateInterval('P1D'));
                }
                $escapedValue = $this->formatDatetime($v, $this->isAllDay);
            } else {
                $escapedValue = $this->escapeString($v);
            }
            $props[strtoupper($realKey)] = $escapedValue;
        }

        // Set some default values
        $props['DTSTAMP'] = $this->formatDatetime(new \DateTime());
        $props['UID'] = uniqid();

        // Append properties
        foreach ($props as $k => $v) {
            $icsProps[] = "{$k}:{$v}";
        }

        // Build ICS properties - add footer
        $icsProps[] = 'END:VEVENT';
        $icsProps[] = 'END:VCALENDAR';

        return $icsProps;
    }

    /**
     * @throws \Exception
     */
    private function formatDatetime(\DateTimeInterface $datetime, bool $allDay = false): string
    {
        $dt = \DateTime::createFromInterface($datetime);
        if (!$this->isAllDay) {
            $dt->setTimezone(new \DateTimeZone('UTC'));
        }

        return $dt->format($allDay ? self::ALL_DAY_DT_FORMAT : self::DT_FORMAT);
    }

    private function escapeString(string $str): string
    {
        return preg_replace('/([,;])/', '\\\$1', $str);
    }
}
