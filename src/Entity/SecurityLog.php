<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SecurityLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SecurityLogRepository::class)]
#[ORM\Table(name: 'security_log')]
#[ORM\Index(name: 'idx_occurred_at', columns: ['occurred_at'])]
#[ORM\Index(name: 'idx_ip_address', columns: ['ip_address'])]
#[ORM\Index(name: 'idx_event_type', columns: ['event_type'])]
class SecurityLog
{
    public const EVENT_FAILED_LOGIN = 'failed_login';

    public const EVENT_ACCOUNT_LOCKED = 'account_locked';

    public const EVENT_CAPTCHA_FAILED = 'captcha_failed';

    public const EVENT_RATE_LIMITED = 'rate_limited';

    public const EVENT_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    public const EVENT_ACCOUNT_UNLOCKED = 'account_unlocked';

    public const EVENT_SUCCESS_LOGIN = 'success_login';

    public const EVENT_SUCCESS_REGISTRATION = 'success_registration';

    public const EVENT_PASSWORD_RESET_REQUESTED = 'password_reset_requested';

    public const EVENT_SUCCESS_PASSWORD_RESET = 'success_password_reset';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private string $email;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 45)]
    private string $ipAddress;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 50)]
    private string $eventType;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): self
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }
}
