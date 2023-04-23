<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;

final class RecurringDateTimeProvider extends Base
{
    /**
     * @param \DateTime|int|string $initialDateTime
     * @param string $recurrence day, week, month, year, ...
     * @param int $offset
     * @return \DateTime|false
     * @throws \Exception
     */
    public static function recurringDateTime(
        \DateTime|int|string $initialDateTime,
        string               $recurrence,
        int                  $offset
    ): \DateTime|false {
        if (is_int($initialDateTime) || is_string($initialDateTime)) {
            $initialDateTime = new \DateTime($initialDateTime);
        }
        $offset = $offset > 0 ? '+' . $offset : $offset;
        return $initialDateTime->modify(sprintf('%s %s', $offset, $recurrence));
    }

    public static function recurringDateTimeImmutable(
        \DateTime|int|string $initialDateTime,
        string               $recurrence,
        int                  $offset
    ): \DateTimeImmutable|false {
        $dateTimeMutable = self::recurringDateTime($initialDateTime, $recurrence, $offset);
        return \DateTimeImmutable::createFromMutable($dateTimeMutable);
    }
}
