<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\State\UserProcessor;
use App\State\UserProvider;
use App\Type\GenderType;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['user:list']]),
        new Get(normalizationContext: ['groups' => ['user:read']]),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['user:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') or object == user",
            denormalizationContext: ['groups' => ['user:write']]
        ),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    provider: UserProvider::class,
    processor: UserProcessor::class
)]
#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[
    UniqueEntity(
        fields: ['email'],
        message: 'There is already an account with this email',
    ),
]
#[ORM\Table(name: 'users')]
#[Auditable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ApiProperty(types: ['https://schema.org/identifier'])]
    #[Groups(['user:read', 'user:list'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\Email]
    #[Assert\NotBlank]
    #[ApiProperty(types: ['https://schema.org/email'])]
    #[Groups(['user:read', 'user:list', 'user:write'])]
    public string $email = '';

    #[ORM\Column(type: Types::JSON)]
    #[ApiProperty(jsonldContext: ['@type' => 'http://www.w3.org/2001/XMLSchema#array'])]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    #[Groups(['user:write'])]
    private string $password = '';

    #[ORM\Column(type: Types::STRING, enumType: GenderType::class)]
    #[Assert\NotBlank]
    #[ApiProperty(types: ['https://schema.org/gender'])]
    #[Groups(['user:read', 'user:list', 'user:write'])]
    public GenderType $gender = GenderType::Other;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[ApiProperty(types: ['https://schema.org/familyName'])]
    #[Groups(['user:read', 'user:list', 'user:write'])]
    public string $familyName = '';

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[ApiProperty(types: ['https://schema.org/givenName'])]
    #[Groups(['user:read', 'user:list', 'user:write'])]
    public string $givenName = '';

    #[ORM\Column(type: Types::STRING, length: 12, nullable: true)]
    #[ApiProperty(types: ['https://schema.org/telephone'])]
    #[Groups(['user:read', 'user:write'])]
    public ?string $telephone = null;

    /**
     * @var Collection<int, Licensee>
     */
    #[
        ORM\OneToMany(
            mappedBy: 'user',
            targetEntity: Licensee::class,
            cascade: ['remove'],
            fetch: 'EAGER',
        ),
    ]
    public iterable $licensees;

    #[ORM\Column]
    public bool $isVerified = false;

    #[ORM\Column(nullable: true)]
    public ?string $discordId = null;

    #[Encrypted]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $discordAccessToken = null;

    public function __construct()
    {
        $this->licensees = new ArrayCollection();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getFullname();
    }

    public function __serialize()
    {
        return [
            'id' => $this->getId(),
            'email' => $this->email,
            'roles' => $this->getRoles(),
            'password' => $this->getPassword(),
        ];
    }

    public function __unserialize(array $data)
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->setRoles($data['roles'])
            ->setPassword($data['password']);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    #[\Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    #[\Override]
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    #[\Override]
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFullname(): string
    {
        return \sprintf('%s %s', $this->givenName, $this->familyName);
    }

    public function hasLicenseeWithCode(string $fftaCode): bool
    {
        return $this->getLicenseeWithCode($fftaCode) instanceof Licensee;
    }

    public function getLicenseeWithCode(string $fftaCode): ?Licensee
    {
        foreach ($this->licensees as $licensee) {
            if ($licensee->fftaMemberCode === $fftaCode) {
                return $licensee;
            }
        }

        return null;
    }
}
