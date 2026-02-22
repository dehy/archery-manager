<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\ClubApplication;
use App\Entity\User;
use App\Helper\SeasonHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ClubApplicationVoter extends Voter
{
    final public const string MANAGE = 'manage';

    public function __construct(
        private readonly SeasonHelper $seasonHelper,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute && $subject instanceof ClubApplication;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var ClubApplication $application */
        $application = $subject;

        return match ($attribute) {
            self::MANAGE => $this->canManage($application, $user),
            default => false,
        };
    }

    private function canManage(ClubApplication $application, User $user): bool
    {
        // Admins can manage all applications
        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Club admins can manage applications for their clubs
        if (!\in_array('ROLE_CLUB_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        return $this->hasMatchingClub($application, $user);
    }

    private function hasMatchingClub(ClubApplication $application, User $user): bool
    {
        $licensees = $user->getLicensees();
        foreach ($licensees as $licensee) {
            $license = $licensee->getLicenseForSeason(
                $this->seasonHelper->getSelectedSeason(),
            );

            if (null !== $license && $license->getClub() === $application->getClub()) {
                return true;
            }
        }

        return false;
    }
}
