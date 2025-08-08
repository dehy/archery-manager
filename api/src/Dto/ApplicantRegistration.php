<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\ApplicantRegistrationProcessor;
use App\Type\PracticeLevelType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for applicant registration
 * Following API Platform 4.0 best practice of separating public API from internal models
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/applicant-registrations',
            processor: ApplicantRegistrationProcessor::class
        )
    ]
)]
class ApplicantRegistration
{
    #[Assert\Email]
    #[Assert\NotBlank]
    public string $email = '';

    #[Assert\NotBlank]
    public string $familyName = '';

    #[Assert\NotBlank]
    public string $givenName = '';

    #[Assert\NotNull]
    public \DateTimeImmutable $birthDate;

    public ?PracticeLevelType $practiceLevel = null;

    public ?string $phoneNumber = null;

    public ?string $comment = null;

    #[Assert\NotBlank]
    public int $season = 0;

    public bool $renewal = false;

    public function __construct()
    {
        $this->birthDate = new \DateTimeImmutable();
    }
}
