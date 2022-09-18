<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "`user`")]
#[
    UniqueEntity(
        fields: ["email"],
        message: "There is already an account with this email"
    )
]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 180, unique: true)]
    #[Assert\Email]
    private $email;

    #[ORM\Column(type: "json")]
    private $roles = [];

    #[ORM\Column(type: "string")]
    private $password;

    #[ORM\Column(type: "GenderType")]
    private $gender;

    #[ORM\Column(type: "string", length: 255)]
    private $lastname;

    #[ORM\Column(type: "string", length: 255)]
    private $firstname;

    #[ORM\Column(type: "string", length: 12, nullable: true)]
    private $phoneNumber;

    #[
        ORM\OneToMany(
            mappedBy: "user",
            targetEntity: Licensee::class,
            indexBy: "fftaMemberCode"
        )
    ]
    private $licensees;

    public function __construct()
    {
        $this->licensees = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getFullname();
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
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = "ROLE_USER";

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
        return sprintf("%s %s", $this->getFirstname(), $this->getLastname());
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
}
