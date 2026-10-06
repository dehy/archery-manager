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
    /**
     * Same prefix as the `api` firewall pattern (^/api/v1) in security.yaml.
     */
    public const string PATH_PREFIX = '/api/v1';

    public const string HEADER_LICENSEE = 'X-Licensee';

    public const string HEADER_SEASON = 'X-Season';

    /**
     * The path is decoded the way the firewall's path matcher and the router decode it, so
     * a percent-encoded URL can't reach the API firewall while being treated as a web request here.
     */
    public static function is(?Request $request): bool
    {
        return $request instanceof Request
            && str_starts_with(rawurldecode($request->getPathInfo()), self::PATH_PREFIX);
    }
}
