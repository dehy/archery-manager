<?php

declare(strict_types=1);

namespace App\Security\Api;

/**
 * The refresh was refused because the account is locked. The session is kept:
 * the device can refresh again once the lock expires.
 */
final class RefreshAccountLockedException extends InvalidRefreshTokenException
{
}
