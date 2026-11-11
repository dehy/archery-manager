<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\ClubHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LicenseeVoter extends Voter
{
    final public const string RENEW = 'RENEW';

    final public const string CHANGE_USER = 'CHANGE_USER';

    public function __construct(
        private readonly ClubHelper $clubHelper,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::RENEW, self::CHANGE_USER], true) && $subject instanceof Licensee;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Licensee $licensee */
        $licensee = $subject;

        return match ($attribute) {
            self::RENEW, self::CHANGE_USER => $this->canManage($licensee, $user),
            default => false,
        };
    }

    private function canManage(Licensee $licensee, User $user): bool
    {
        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (!\in_array('ROLE_CLUB_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        $club = $this->clubHelper->getClubForUser($user);

        return $club instanceof \App\Entity\Club && $licensee->hasLicenseForClub($club);
    }
}
