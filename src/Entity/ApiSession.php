<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApiSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One authenticated device of the mobile API.
 *
 * Only SHA-256 hashes of the opaque access and refresh tokens are stored.
 * Refreshing rotates both tokens in place (see ApiSessionRepository::rotateIfUnchanged); the
 * previous refresh hash is kept so that a replayed (stolen) refresh token revokes the whole session.
 */
#[ORM\Entity(repositoryClass: ApiSessionRepository::class)]
#[ORM\Table(name: 'api_session')]
#[ORM\Index(name: 'idx_api_session_previous_refresh', columns: ['previous_refresh_token_hash'])]
class ApiSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $previousRefreshTokenHash = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $rotatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
        private string $accessTokenHash,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $accessTokenExpiresAt,
        #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
        private string $refreshTokenHash,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $refreshTokenExpiresAt,
        #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
        private ?string $deviceName = null,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function getAccessTokenHash(): string
    {
        return $this->accessTokenHash;
    }

    public function getAccessTokenExpiresAt(): \DateTimeImmutable
    {
        return $this->accessTokenExpiresAt;
    }

    public function getRefreshTokenHash(): string
    {
        return $this->refreshTokenHash;
    }

    public function getPreviousRefreshTokenHash(): ?string
    {
        return $this->previousRefreshTokenHash;
    }

    public function getRefreshTokenExpiresAt(): \DateTimeImmutable
    {
        return $this->refreshTokenExpiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function touch(\DateTimeImmutable $now): void
    {
        $this->lastUsedAt = $now;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt instanceof \DateTimeImmutable;
    }

    public function revoke(\DateTimeImmutable $now): void
    {
        $this->revokedAt = $now;
    }

    public function isAccessTokenValid(\DateTimeImmutable $now): bool
    {
        return !$this->isRevoked() && $this->accessTokenExpiresAt > $now;
    }

    public function isRefreshTokenValid(\DateTimeImmutable $now): bool
    {
        return !$this->isRevoked() && $this->refreshTokenExpiresAt > $now;
    }

    public function getRotatedAt(): ?\DateTimeImmutable
    {
        return $this->rotatedAt;
    }

    /**
     * Whether the refresh token was rotated at most $seconds ago. The previous token is
     * still honoured in that window, because the client may never have received the new pair.
     */
    public function wasRotatedWithin(int $seconds, \DateTimeImmutable $now): bool
    {
        return $this->rotatedAt instanceof \DateTimeImmutable
            && $now->getTimestamp() - $this->rotatedAt->getTimestamp() <= $seconds;
    }
}
