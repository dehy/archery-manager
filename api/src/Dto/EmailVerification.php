<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\EmailVerificationProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for email verification
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/verify-email',
            processor: EmailVerificationProcessor::class,
            status: 200,
            output: EmailVerificationResponse::class,
        ),
    ]
)]
class EmailVerification
{
    #[Assert\NotBlank]
    public string $token = '';
}
