<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\TokenRefreshProcessor;

/**
 * DTO for token refresh
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/refresh-token',
            processor: TokenRefreshProcessor::class,
            output: TokenResult::class,
            status: 200,
            security: 'is_granted("ROLE_USER")',
            normalizationContext: ['groups' => ['token:read']],
        ),
    ]
)]
class TokenRefresh
{
    // This DTO is empty as the token refresh only needs the authenticated user
}
