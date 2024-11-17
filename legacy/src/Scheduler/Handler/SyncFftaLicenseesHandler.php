<?php

declare(strict_types=1);

namespace App\Scheduler\Handler;

use App\Helper\FftaHelper;
use App\Repository\ClubRepository;
use App\Scheduler\Message\SyncFftaLicensees;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SyncFftaLicenseesHandler
{
    public function __construct(
        private FftaHelper $fftaHelper,
        private ClubRepository $clubRepository,
    ) {
    }

    public function __invoke(SyncFftaLicensees $message): void
    {
        $club = $this->clubRepository->findOneByCode($message->getClubCode());
        $this->fftaHelper->syncLicensees($club, $message->getSeason());
    }
}
