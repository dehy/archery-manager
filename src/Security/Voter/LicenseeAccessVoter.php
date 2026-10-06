<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\SeasonHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Who may see a licensee's data.
 *
 * VIEW (profile, attachments such as medical certificates): the licensee's own account, an admin,
 * or a club admin / coach of the same club in the selected season. This is the rule of the web profile page.
 *
 * VIEW_PICTURE (profile picture): additionally any member of the same club, since the member
 * directory shows every club member's picture.
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

        return (self::VIEW_PICTURE === $attribute || $privileged) && $this->sharesClub($user, $subject);
    }

    /**
     * Whether the user and the licensee hold a license of the same club for the selected season.
     */
    private function sharesClub(User $user, Licensee $licensee): bool
    {
        $season = $this->seasonHelper->getSelectedSeason();
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
