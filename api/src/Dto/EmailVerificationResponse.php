<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * DTO for email verification response
 */
class EmailVerificationResponse
{
    public bool $verified = false;
    public string $message = '';

    public function __construct(bool $verified, string $message)
    {
        $this->verified = $verified;
        $this->message = $message;
    }
}
