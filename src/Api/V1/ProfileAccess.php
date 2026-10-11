<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Repository\LicenseRepository;
use App\Security\Voter\LicenseeAccessVoter;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Which licensees of a list the viewer may open the profile of (`can_view_profile`), so the app only makes
 * the rows it can open tappable. Same rule as LicenseeAccessVoter::VIEW, but for a whole list in at most
 * one query: the voter cannot be called row by row on a directory page, whose licensees hold the licenses
 * of the selected season only, and that would cost a query per row besides.
 */
final readonly class ProfileAccess
{
    public function __construct(
        private Security $security,
        private RoleHierarchyInterface $roleHierarchy,
        private LicenseRepository $licenses,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param list<Licensee> $licensees
     *
     * @return array<int, true> licensee id => true, for those whose profile the viewer may open
     */
    public function viewable(array $licensees): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || [] === $licensees) {
            return [];
        }

        $roles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());
        if (\in_array('ROLE_ADMIN', $roles, true)) {
            return array_fill_keys(array_map(static fn (Licensee $licensee): int => (int) $licensee->getId(), $licensees), true);
        }

        $own = array_values($user->getLicensees()->toArray());
        $viewable = array_fill_keys(array_map(static fn (Licensee $licensee): int => (int) $licensee->getId(), $own), true);

        if (\in_array('ROLE_CLUB_ADMIN', $roles, true) || \in_array('ROLE_COACH', $roles, true)) {
            $season = Season::seasonForDate($this->clock->now());
            foreach ($this->licenses->idsSharingAClubWith($licensees, $own, $season) as $id) {
                $viewable[$id] = true;
            }
        }

        return $viewable;
    }
}
