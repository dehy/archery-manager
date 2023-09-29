<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;
use Faker\Provider\DateTime;

final class DateTimeImmutableProvider extends Base
{
    /**
     * Get a datetime immutable object for a date between January 1, 1970 and now.
     *
     * @param \DateTime|int|string $max      maximum timestamp used as random end limit, default to "now"
     * @param string               $timezone time zone in which the date time should be set, default to DateTime::$defaultTimezone, if set, otherwise the result of `date_default_timezone_get`
     **
     * @see http://php.net/manual/en/timezones.php
     * @see http://php.net/manual/en/function.date-default-timezone-get.php
     *
     * @example DateTimeImmutable('2005-08-16 20:39:21')
     */
    public static function dateTimeImmutable(\DateTime|int|string $max = 'now', string $timezone = null): \DateTimeImmutable
    {
        $datetimeMutable = DateTime::dateTime($max, $timezone);

        return \DateTimeImmutable::createFromMutable($datetimeMutable);
    }
}
