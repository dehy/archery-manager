<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ConsentLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsentLogRepository::class)]
#[ORM\Table(name: 'consent_log')]
class ConsentLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    /** @var array<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $services = [];

    #[ORM\Column(type: Types::STRING, length: 16)]
    private string $action;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $policyVersion;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $ipAddressAnonymized;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /** @return array<string> */
    public function getServices(): array
    {
        return $this->services;
    }

    /** @param array<string> $services */
    public function setServices(array $services): static
    {
        $this->services = $services;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getPolicyVersion(): string
    {
        return $this->policyVersion;
    }

    public function setPolicyVersion(string $policyVersion): static
    {
        $this->policyVersion = $policyVersion;

        return $this;
    }

    public function getIpAddressAnonymized(): string
    {
        return $this->ipAddressAnonymized;
    }

    public function setIpAddressAnonymized(string $ipAddressAnonymized): static
    {
        $this->ipAddressAnonymized = $ipAddressAnonymized;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
