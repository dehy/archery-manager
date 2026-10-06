<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Entity\User;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Applies the web lockout rule to the API firewalls, for logins and for every bearer-token request.
 *
 * A user without a password hash is deliberately not special-cased: it fails as plain bad credentials.
 *
 * Known trade-off: this runs before the password check, so a locked account answers with the
 * lock notice (account_locked) while an unknown email gets invalid_credentials. That reveals that
 * a locked email exists, in exchange for telling real users why they can't log in.
 */
final readonly class ApiUserChecker implements UserCheckerInterface
{
    public function __construct(private ClockInterface $clock)
    {
    }

    #[\Override]
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User || !$user->isAccountLocked()) {
            return;
        }

        $lockedUntil = $user->getAccountLockedUntil();
        $remainingMinutes = $lockedUntil instanceof \DateTimeImmutable
            ? max(1, (int) ceil(($lockedUntil->getTimestamp() - $this->clock->now()->getTimestamp()) / 60))
            : 1;

        throw new CustomUserMessageAccountStatusException(\sprintf('Account temporarily locked. Try again in %d minutes.', $remainingMinutes));
    }

    #[\Override]
    public function checkPostAuth(UserInterface $user): void
    {
    }
}
