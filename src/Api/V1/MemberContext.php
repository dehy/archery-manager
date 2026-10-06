<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\Entity\Club;
use App\Entity\License;
use App\Entity\Licensee;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The member a request acts as: the licensee selected with X-Licensee, the season selected
 * with X-Season, and the club of the licensee's license for that season.
 *
 * Mirrors the web app's `assertHasValidLicense()`: club-scoped screens need a license for the season.
 */
final readonly class MemberContext
{
    public function __construct(
        private LicenseeHelper $licenseeHelper,
        private SeasonHelper $seasonHelper,
    ) {
    }

    public function season(): int
    {
        return $this->seasonHelper->getSelectedSeason();
    }

    public function licensee(): ?Licensee
    {
        return $this->licenseeHelper->getLicenseeFromSession();
    }

    /**
     * @throws AccessDeniedHttpException when there is no licensee or no license for the selected season
     */
    public function requireLicense(): License
    {
        $license = $this->licensee()?->getLicenseForSeason($this->season());

        return $license ?? throw new AccessDeniedHttpException('No valid license for the selected season.');
    }

    /**
     * @throws AccessDeniedHttpException
     */
    public function requireClub(): Club
    {
        return $this->requireLicense()->getClub() ?? throw new AccessDeniedHttpException('The license of the selected season has no club.');
    }
}
