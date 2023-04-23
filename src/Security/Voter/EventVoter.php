<?php

namespace App\Security\Voter;

use App\DBAL\Types\UserRoleType;
use App\Entity\Event;
use App\Entity\Season;
use App\Entity\User;
use App\Helper\LicenseeHelper;
use App\Helper\LicenseHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class EventVoter extends Voter
{
    public const EDIT = 'EVENT_EDIT';
    public const VIEW = 'EVENT_VIEW';

    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return \in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof \App\Entity\Event;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
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
        array_filter($clubs);

        // ... (check conditions and return true to grant permission) ...
        return match ($attribute) {
            self::EDIT => $this->security->isGranted(UserRoleType::ADMIN, $user),
            self::VIEW => \in_array($event->getClub(), $clubs),
            default => false,
        };
    }
}
