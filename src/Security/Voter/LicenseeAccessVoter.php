<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Helper\SeasonHelper;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Who may see a licensee's data.
 *
 * VIEW (profile, attachments such as medical certificates): the licensee's own account, an admin,
 * or a club admin / coach who shares a club with the licensee **this season** (the current one, whatever
 * season the request selects). The web profile page applies the same rule to the selected season; the API is
 * stricter so that picking an old season cannot reach the medical certificates of people who have left.
 *
 * VIEW_PICTURE (profile picture): additionally any member of the same club in the **selected** season,
 * since the member directory of that season shows every member's picture.
 *
 * Known limit, shared with the web: ROLE_COACH / ROLE_CLUB_ADMIN belong to the account, not to a club.
 * A coach of club A whose account also holds a licensee in club B is a coach for club B too. Fixing it
 * needs per-club roles.
 *
 * @extends Voter<string, Licensee>
 */
final class LicenseeAccessVoter extends Voter
{
    public const string VIEW = 'LICENSEE_VIEW';

    public const string VIEW_PICTURE = 'LICENSEE_VIEW_PICTURE';

    public function __construct(
        private readonly SeasonHelper $seasonHelper,
        private readonly RoleHierarchyInterface $roleHierarchy,
        private readonly ClockInterface $clock,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::VIEW_PICTURE], true) && $subject instanceof Licensee;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$subject instanceof Licensee) {
            return false;
        }

        $roles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());
        if ($user->getLicensees()->contains($subject) || \in_array('ROLE_ADMIN', $roles, true)) {
            return true;
        }

        $privileged = \in_array('ROLE_CLUB_ADMIN', $roles, true) || \in_array('ROLE_COACH', $roles, true);

        return match ($attribute) {
            self::VIEW_PICTURE => $this->sharesClub($user, $subject, $this->seasonHelper->getSelectedSeason()),
            default => $privileged && $this->sharesClub($user, $subject, Season::seasonForDate($this->clock->now())),
        };
    }

    /**
     * Whether the user and the licensee hold a license of the same club for the given season.
     */
    private function sharesClub(User $user, Licensee $licensee, int $season): bool
    {
        $targetClub = $licensee->getLicenseForSeason($season)?->getClub();
        if (!$targetClub instanceof \App\Entity\Club) {
            return false;
        }

        foreach ($user->getLicensees() as $userLicensee) {
            $license = $userLicensee->getLicenseForSeason($season);
            if ($license instanceof License && $license->getClub() === $targetClub) {
                return true;
            }
        }

        return false;
    }
}
