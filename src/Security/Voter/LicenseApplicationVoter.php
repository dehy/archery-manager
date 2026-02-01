<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\LicenseApplication;
use App\Entity\User;
use App\Helper\SeasonHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LicenseApplicationVoter extends Voter
{
    final public const string MANAGE = 'manage';

    public function __construct(
        private readonly SeasonHelper $seasonHelper,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute && $subject instanceof LicenseApplication;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var LicenseApplication $application */
        $application = $subject;

        return match ($attribute) {
            self::MANAGE => $this->canManage($application, $user),
            default => false,
        };
    }

    private function canManage(LicenseApplication $application, User $user): bool
    {
        // Admins can manage all applications
        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Club admins can manage applications for their clubs
        if (!\in_array('ROLE_CLUB_ADMIN', $user->getRoles(), true)) {
            return false;
        }

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
