<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DTO for token refresh result
 */
class TokenResult
{
    #[Groups(['token:read'])]
    public string $token = '';

    public function __construct(string $token)
    {
        $this->token = $token;
    }
}
