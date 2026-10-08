<?php

declare(strict_types=1);

namespace App\Api\V1;

/**
 * The answer sent for an event breaks one of its rules. The message is meant for the client developer.
 */
final class InvalidParticipationException extends \DomainException
{
}
