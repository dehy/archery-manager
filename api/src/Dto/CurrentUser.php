<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\CurrentUserProvider;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DTO for current user info
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/me',
            provider: CurrentUserProvider::class,
            security: 'is_granted("ROLE_USER")',
            normalizationContext: ['groups' => ['user:me']],
        ),
    ]
)]
class CurrentUser
{
    #[Groups(['user:me'])]
    public ?int $id = null;
    
    #[Groups(['user:me'])]
    public string $email = '';
    
    #[Groups(['user:me'])]
    public string $givenName = '';
    
    #[Groups(['user:me'])]
    public string $familyName = '';
    
    #[Groups(['user:me'])]
    public ?string $gender = null;
    
    #[Groups(['user:me'])]
    public ?string $telephone = null;
    
    #[Groups(['user:me'])]
    public array $roles = [];
    
    #[Groups(['user:me'])]
    public bool $isVerified = false;
    
    #[Groups(['user:me'])]
    public array $licensees = [];
}
