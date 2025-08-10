<?php

declare(strict_types=1);

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use App\Type\GenderType;

final class LicenseeProfile
{
    #[ApiProperty(types: ['https://schema.org/givenName'])]
    public string $givenName;

    #[ApiProperty(types: ['https://schema.org/familyName'])]
    public string $familyName;

    #[ApiProperty(types: ['https://schema.org/birthDate'])]
    public ?\DateTimeInterface $birthDate = null;

    #[ApiProperty(types: ['https://schema.org/gender'])]
    public ?GenderType $gender = null;

    #[ApiProperty(types: ['https://schema.org/email'])]
    public ?string $email = null;

    #[ApiProperty(types: ['https://schema.org/telephone'])]
    public ?string $phoneNumber = null;

    public function __construct(
        string $givenName,
        string $familyName,
        ?\DateTimeInterface $birthDate = null,
        ?GenderType $gender = null,
        ?string $email = null,
        ?string $phoneNumber = null,
    ) {
        $this->givenName = $givenName;
        $this->familyName = $familyName;
        $this->birthDate = $birthDate;
        $this->gender = $gender;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
    }
}
