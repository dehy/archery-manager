<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\License;

final class ClubApplicationApprovalData
{
    public function __construct(
        private readonly License $license,
        private ?string $adminMessage = null,
    ) {
    }

    public function getLicense(): License
    {
        return $this->license;
    }

    public function getAdminMessage(): ?string
    {
        return $this->adminMessage;
    }

    public function setAdminMessage(?string $adminMessage): void
    {
        $this->adminMessage = $adminMessage;
    }
}
