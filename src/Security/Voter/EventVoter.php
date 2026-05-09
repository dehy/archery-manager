<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\DBAL\Types\UserRoleType;
use App\Entity\Event;
use App\Entity\Season;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class EventVoter extends Voter
{
    final public const string EDIT = 'EVENT_EDIT';

    final public const string DELETE = 'EVENT_DELETE';

    final public const string VIEW = 'EVENT_VIEW';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return \in_array($attribute, [self::EDIT, self::DELETE, self::VIEW], true)
            && $subject instanceof Event;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Event $event */
        $event = $subject;
        $eventSeason = Season::seasonForDate($event->getEndsAt());

        /** @var User $user */
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        $clubs = [];
        foreach ($user->getLicensees() as $licensee) {
            $clubs[] = $licensee->getLicenseForSeason($eventSeason)?->getClub();
        }

        $clubs = array_filter($clubs);

        $isClubAdmin = $this->security->isGranted(UserRoleType::CLUB_ADMIN, $user);
        $isAdmin = $this->security->isGranted(UserRoleType::ADMIN, $user);
        $eventBelongsToUsersClub = \in_array($event->getClub(), $clubs, true);

        // ... (check conditions and return true to grant permission) ...
        return match ($attribute) {
            self::EDIT, self::DELETE => $isAdmin || ($isClubAdmin && $eventBelongsToUsersClub),
            self::VIEW => $eventBelongsToUsersClub,
            default => false,
        };
    }
}
