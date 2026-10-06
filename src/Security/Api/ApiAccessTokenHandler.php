<?php

declare(strict_types=1);

namespace App\Security\Api;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final readonly class ApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private ApiTokenManager $tokenManager)
    {
    }

    #[\Override]
    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $session = $this->tokenManager->findValidSessionByAccessToken($accessToken);
        if (!$session instanceof \App\Entity\ApiSession) {
            throw new BadCredentialsException('Invalid or expired access token.');
        }

        return new UserBadge($session->getUser()->getUserIdentifier());
    }
}
