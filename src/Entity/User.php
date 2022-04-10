<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string')]
    private $password;

    #[ORM\Column(type: 'GenderType')]
    private $gender;

    #[ORM\Column(type: 'string', length: 255)]
    private $lastname;

    #[ORM\Column(type: 'string', length: 255)]
    private $firstname;

    #[ORM\Column(type: 'date')]
    private $birthdate;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: License::class, orphanRemoval: true)]
    private $licenses;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Bow::class, orphanRemoval: true)]
    private $bows;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Arrow::class, orphanRemoval: true)]
    private $arrows;

    #[ORM\OneToMany(mappedBy: 'participant', targetEntity: EventParticipation::class, orphanRemoval: true)]
    private $eventParticipations;

    public function __construct()
    {
        $this->arrows = new ArrayCollection();
        $this->bows = new ArrayCollection();
        $this->licenses = new ArrayCollection();
        $this->eventParticipations = new ArrayCollection();
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

    public function getBirthdate(): ?DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * @return Collection<int, License>
     */
    public function getLicenses(): Collection
    {
        return $this->licenses;
    }

    public function addLicense(License $license): self
    {
        if (!$this->licenses->contains($license)) {
            $this->licenses[] = $license;
            $license->setOwner($this);
        }

        return $this;
    }

    public function removeLicense(License $license): self
    {
        if ($this->licenses->removeElement($license)) {
            // set the owning side to null (unless already changed)
            if ($license->getOwner() === $this) {
                $license->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Bow>
     */
    public function getBows(): Collection
    {
        return $this->bows;
    }

    public function addBow(Bow $bow): self
    {
        if (!$this->bows->contains($bow)) {
            $this->bows[] = $bow;
            $bow->setOwner($this);
        }

        return $this;
    }

    public function removeBow(Bow $bow): self
    {
        if ($this->bows->removeElement($bow)) {
            // set the owning side to null (unless already changed)
            if ($bow->getOwner() === $this) {
                $bow->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Arrow>
     */
    public function getArrows(): Collection
    {
        return $this->arrows;
    }

    public function addArrow(Arrow $arrow): self
    {
        if (!$this->arrows->contains($arrow)) {
            $this->arrows[] = $arrow;
            $arrow->setOwner($this);
        }

        return $this;
    }

    public function removeArrow(Arrow $arrow): self
    {
        if ($this->arrows->removeElement($arrow)) {
            // set the owning side to null (unless already changed)
            if ($arrow->getOwner() === $this) {
                $arrow->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EventParticipation>
     */
    public function getEventParticipations(): Collection
    {
        return $this->eventParticipations;
    }

    public function addEventParticipation(EventParticipation $eventParticipation): self
    {
        if (!$this->eventParticipations->contains($eventParticipation)) {
            $this->eventParticipations[] = $eventParticipation;
            $eventParticipation->setParticipant($this);
        }

        return $this;
    }

    public function removeEventParticipation(EventParticipation $eventParticipation): self
    {
        if ($this->eventParticipations->removeElement($eventParticipation)) {
            // set the owning side to null (unless already changed)
            if ($eventParticipation->getParticipant() === $this) {
                $eventParticipation->setParticipant(null);
            }
        }

        return $this;
    }
}
