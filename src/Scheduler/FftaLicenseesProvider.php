<?php

namespace App\Scheduler;

use App\Entity\Season;
use App\Repository\ClubRepository;
use App\Scheduler\Message\SyncFftaLicensees;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\CallbackMessageProvider;

#[AsSchedule('ffta_licensees')]
class FftaLicenseesProvider implements ScheduleProviderInterface
{
    protected Schedule $schedule;

    public function __construct(private readonly ClubRepository $clubRepository)
    {
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->with(
                RecurringMessage::cron(
                    '#midnight',
                    new CallbackMessageProvider($this->generateSyncLicenseesMessages(...))
                )
            );
    }

    public function generateSyncLicenseesMessages()
    {
        $season = Season::seasonForDate(new \DateTimeImmutable());
        $clubs = $this->clubRepository->findAll();

        foreach ($clubs as $club) {
            yield new SyncFftaLicensees($club->getId(), $club->getFftaCode(), $season);
        }
    }
}
