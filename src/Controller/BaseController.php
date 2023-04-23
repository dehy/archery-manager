<?php

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

        if (!$this->licenseeHelper->getLicenseeFromSession()?->getLicenseForSeason($season)) {
            throw $this->createAccessDeniedException('No valid license for the selected season');
        }
    }
}
