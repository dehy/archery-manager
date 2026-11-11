<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Scheduler\Message\SendCaciReminders;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('caci_reminder')]
class CaciReminderProvider implements ScheduleProviderInterface
{
    protected Schedule $schedule;

    #[\Override]
    public function getSchedule(): Schedule
    {
        return $this->schedule ??= new Schedule()
            ->with(
                RecurringMessage::cron('0 8 1 6 *', new SendCaciReminders()),
                RecurringMessage::cron('0 8 1 8 *', new SendCaciReminders()),
            );
    }
}
