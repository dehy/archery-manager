<?php

declare(strict_types=1);

namespace App\Security\Api;

/**
 * Another request rotated the same session at the same moment. The client should retry shortly:
 * its token is then still accepted as the "previous" one within the grace period.
 */
final class RefreshConflictException extends InvalidRefreshTokenException
{
}
