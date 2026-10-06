<?php

declare(strict_types=1);

namespace App\Security\Api;

/**
 * Plain-text tokens, only ever available at issue time.
 */
final readonly class IssuedTokens
{
    public function __construct(
        public string $accessToken,
        public \DateTimeImmutable $accessTokenExpiresAt,
        public string $refreshToken,
        public \DateTimeImmutable $refreshTokenExpiresAt,
    ) {
    }

    /**
     * @return array{token_type: string, access_token: string, expires_in: int, refresh_token: string}
     */
    public function toArray(\DateTimeImmutable $now): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $this->accessToken,
            'expires_in' => max(0, $this->accessTokenExpiresAt->getTimestamp() - $now->getTimestamp()),
            'refresh_token' => $this->refreshToken,
        ];
    }
}
