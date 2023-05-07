<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[
    UniqueEntity(
        fields: ['email'],
        message: 'There is already an account with this email',
    ),
]
#[Auditable]
class User implements UserInterface, PasswordAuthenticatedUserInterface, \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(type: Types::STRING)]
    private string $password;

    #[ORM\Column(type: 'GenderType')]
    private string $gender;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $lastname;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $firstname;

    #[ORM\Column(type: Types::STRING, length: 12, nullable: true)]
    private ?string $phoneNumber = null;

    /**
     * @var Collection<int, Licensee>|Licensee[]
     */
    #[
        ORM\OneToMany(
            mappedBy: 'user',
            targetEntity: Licensee::class,
            cascade: ['remove']
        ),
    ]
    private Collection $licensees;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(nullable: true)]
    private ?string $discordId = null;

    #[Encrypted]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $discordAccessToken = null;

    public function __construct()
    {
        $this->licensees = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getFullname();
    }

    public function __serialize()
    {
        return [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'roles' => $this->getRoles(),
            'password' => $this->getPassword(),
        ];
    }

    public function __unserialize(array $data)
    {
        $this->id = $data['id'];
        $this->setEmail($data['email'])
            ->setRoles($data['roles'])
            ->setPassword($data['password']);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
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
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getGender()
    {
        return $this->gender;
    }

    public function setGender($gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullname(): string
    {
        return sprintf('%s %s', $this->getFirstname(), $this->getLastname());
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @return Collection<int, Licensee>
     */
    public function getLicensees(): Collection
    {
        return $this->licensees;
    }

    public function addLicensee(Licensee $licensee): self
    {
        if (!$this->licensees->contains($licensee)) {
            $this->licensees[] = $licensee;
            $licensee->setUser($this);
        }

        return $this;
    }

    public function removeLicensee(Licensee $licensee): self
    {
        if ($this->licensees->removeElement($licensee)) {
            // set the owning side to null (unless already changed)
            if ($licensee->getUser() === $this) {
                $licensee->setUser(null);
            }
        }

        return $this;
    }

    public function hasLicenseeWithCode(string $fftaCode): bool
    {
        return isset($this->getLicensees()[$fftaCode]);
    }

    public function getLicenseeWithCode(string $fftaCode): ?Licensee
    {
        return $this->getLicensees()[$fftaCode] ?? null;
    }

    public function isIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getDiscordId(): ?string
    {
        return $this->discordId;
    }

    public function setDiscordId(?string $discordId): self
    {
        $this->discordId = $discordId;

        return $this;
    }

    public function getDiscordAccessToken(): string
    {
        return $this->discordAccessToken;
    }

    public function setDiscordAccessToken(?string $discordAccessToken): self
    {
        $this->discordAccessToken = $discordAccessToken;

        return $this;
    }
}
