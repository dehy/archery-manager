<?php

declare(strict_types=1);

namespace App\Security\Api;

/**
 * An already-rotated refresh token was presented again: it was probably stolen.
 */
final class RefreshTokenReuseException extends InvalidRefreshTokenException
{
    public function __construct(public readonly \App\Entity\User $user)
    {
        parent::__construct('Refresh token reuse detected.');
    }
}
