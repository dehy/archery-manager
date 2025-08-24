<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Entity\User;
use App\State\UserRegistrationProcessor;
use App\Type\GenderType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for user registration
 * Following API Platform 4.0 best practice of separating public API from internal models.
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/register',
            processor: UserRegistrationProcessor::class,
            output: User::class,
            normalizationContext: ['groups' => ['user:read']],
        ),
    ]
)]
class UserRegistration
{
    #[Assert\Email]
    #[Assert\NotBlank]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password = '';

    #[Assert\NotBlank]
    public string $givenName = '';

    #[Assert\NotBlank]
    public string $familyName = '';

    #[Assert\NotNull]
    public ?GenderType $gender = null;

    public ?string $telephone = null;
}
