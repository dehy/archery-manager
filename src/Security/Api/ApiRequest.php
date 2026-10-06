<?php

declare(strict_types=1);

namespace App\Security\Api;

use Symfony\Component\HttpFoundation\Request;

/**
 * Identifies mobile API requests and names the headers carrying the request-scoped context
 * that the web app keeps in the session (selected licensee and season).
 */
final class ApiRequest
{
    public const string PATH_PREFIX = '/api/v1/';

    public const string HEADER_LICENSEE = 'X-Licensee';

    public const string HEADER_SEASON = 'X-Season';

    public static function is(?Request $request): bool
    {
        return $request instanceof Request && str_starts_with($request->getPathInfo(), self::PATH_PREFIX);
    }
}
