<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class BaseController extends AbstractController
{
    public function __construct(
        protected readonly LicenseeHelper $licenseeHelper,
        protected readonly SeasonHelper $seasonHelper
    ) {
    }

    protected function assertHasValidLicense(): void
    {
        $season = $this->seasonHelper->getSelectedSeason();

        if (!$this->licenseeHelper->getLicenseeFromSession()?->getLicenseForSeason($season) instanceof \App\Entity\License) {
            throw $this->createAccessDeniedException('No valid license for the selected season');
        }
    }
}
