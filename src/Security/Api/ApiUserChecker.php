<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Applies the web lockout rule to the API firewall, for logins and for every bearer-token request.
 *
 * A user without a password hash is deliberately not special-cased: it fails as plain
 * bad credentials so the login response doesn't reveal which emails exist.
 */
final class ApiUserChecker implements UserCheckerInterface
{
    #[\Override]
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User || !$user->isAccountLocked()) {
            return;
        }

        $lockedUntil = $user->getAccountLockedUntil();
        $remainingMinutes = $lockedUntil instanceof \DateTimeImmutable
            ? max(1, (int) ceil(($lockedUntil->getTimestamp() - time()) / 60))
            : 1;

        throw new CustomUserMessageAccountStatusException(\sprintf('Compte temporairement verrouillé. Réessayez dans %d minutes.', $remainingMinutes));
    }

    #[\Override]
    public function checkPostAuth(UserInterface $user): void
    {
    }
}
