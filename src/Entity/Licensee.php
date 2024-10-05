<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\DBAL\Types\LicenseeAttachmentType;
use App\Helper\ObjectComparator;
use App\Helper\SyncReturnValues;
use App\Repository\LicenseeRepository;
use DH\Auditor\Provider\Doctrine\Auditing\Annotation\Auditable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LicenseeRepository::class)]
#[Auditable]
#[ApiResource]
class Licensee implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'licensees')]
    #[ORM\JoinColumn]
    private User $user;

    #[ORM\Column(type: 'GenderType')]
    private string $gender;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private string $lastname;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private string $firstname;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE)]
    private \DateTimeInterface $birthdate;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 8, unique: true, nullable: true)]
    #[Assert\Length(min: 8, max: 8)]
    private ?string $fftaMemberCode = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, unique: true, nullable: true)]
    private ?int $fftaId = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, License>|License[]
     */
    #[
        ORM\OneToMany(
            mappedBy: 'licensee',
            targetEntity: License::class,
            orphanRemoval: true,
        ),
    ]
    private Collection $licenses;

    /**
     * @var Collection<int, Bow>|Bow[]
     */
    #[
        ORM\OneToMany(
            mappedBy: 'owner',
            targetEntity: Bow::class,
            orphanRemoval: true,
        ),
    ]
    private Collection $bows;

    /**
     * @var Collection<int, Arrow>|Arrow[]
     */
    #[
        ORM\OneToMany(
            mappedBy: 'owner',
            targetEntity: Arrow::class,
            orphanRemoval: true,
        ),
    ]
    private Collection $arrows;

    /**
     * @var Collection<int, EventParticipation>|EventParticipation[]
     */
    #[
        ORM\OneToMany(
            mappedBy: 'participant',
            targetEntity: EventParticipation::class,
            orphanRemoval: true,
        ),
    ]
    private Collection $eventParticipations;

    /**
     * @var Collection<int, Result>|Result[]
     */
    #[
        ORM\OneToMany(
            mappedBy: 'licensee',
            targetEntity: Result::class,
            orphanRemoval: true,
        ),
    ]
    private Collection $results;

    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'licensees')]
    private Collection $groups;

    /**
     * @var Collection<int, PracticeAdvice>|PracticeAdvice[]
     */
    #[
        ORM\OneToMany(
            mappedBy: 'licensee',
            targetEntity: PracticeAdvice::class,
            orphanRemoval: true,
        ),
    ]
    private Collection $practiceAdvices;

    /**
     * @var Collection<int, PracticeAdvice>|PracticeAdvice[]
     */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: PracticeAdvice::class)]
    private Collection $givenPracticeAdvices;

    /**
     * @var Collection<int, LicenseeAttachment>
     */
    #[ORM\OneToMany(mappedBy: 'licensee', targetEntity: LicenseeAttachment::class, orphanRemoval: true)]
    private Collection $attachments;

    /**
     * @var ArrayCollection<int, Club>
     */
    protected ArrayCollection $clubs;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->arrows = new ArrayCollection();
        $this->bows = new ArrayCollection();
        $this->licenses = new ArrayCollection();
        $this->eventParticipations = new ArrayCollection();
        $this->results = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->practiceAdvices = new ArrayCollection();
        $this->givenPracticeAdvices = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getFullname();
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

        $user->addLicensee($this);

        return $this;
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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getFullname(): string
    {
        return sprintf('%s %s', $this->getFirstname(), $this->getLastname());
    }

    public function getFirstnameWithInitial(): string
    {
        return sprintf('%s %s.', $this->getFirstname(), strtoupper(substr($this->getLastname(), 0, 1)));
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function getAge(): int
    {
        return $this->getBirthdate()->diff(new \DateTime())->y;
    }

    public function setBirthdate(\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getFftaId(): ?int
    {
        return $this->fftaId;
    }

    public function setFftaId(?int $fftaId): self
    {
        $this->fftaId = $fftaId;

        return $this;
    }

    public function getFftaMemberCode(): ?string
    {
        return $this->fftaMemberCode;
    }

    public function setFftaMemberCode(?string $fftaMemberCode): self
    {
        $this->fftaMemberCode = $fftaMemberCode;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, License>
     */
    public function getLicenses(): Collection
    {
        return $this->licenses;
    }

    public function getLicenseForSeason(int $year): ?License
    {
        $filteredLicenses = $this->licenses->filter(
            fn (License $l) => $l->getSeason() === $year,
        );

        if ($filteredLicenses->count() > 1) {
            throw new \LogicException('Licensee should not have multiple licenses for same season');
        }

        return 1 === $filteredLicenses->count()
            ? $filteredLicenses->first()
            : null;
    }

    public function addLicense(License $license): self
    {
        if (!$this->licenses->contains($license)) {
            $this->licenses[] = $license;
            $license->setLicensee($this);
        }

        return $this;
    }

    public function removeLicense(License $license): self
    {
        if ($this->licenses->removeElement($license)) {
            // set the owning side to null (unless already changed)
            if ($license->getLicensee() === $this) {
                $license->setLicensee(null);
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

    public function addEventParticipation(
        EventParticipation $eventParticipation,
    ): self {
        if (!$this->eventParticipations->contains($eventParticipation)) {
            $this->eventParticipations[] = $eventParticipation;
            $eventParticipation->setParticipant($this);
        }

        return $this;
    }

    public function removeEventParticipation(
        EventParticipation $eventParticipation,
    ): self {
        if ($this->eventParticipations->removeElement($eventParticipation)) {
            // set the owning side to null (unless already changed)
            if ($eventParticipation->getParticipant() === $this) {
                $eventParticipation->setParticipant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Result>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(Result $result): self
    {
        if (!$this->results->contains($result)) {
            $this->results[] = $result;
            $result->setLicensee($this);
        }

        return $this;
    }

    public function removeResult(Result $result): self
    {
        if ($this->results->removeElement($result)) {
            // set the owning side to null (unless already changed)
            if ($result->getLicensee() === $this) {
                $result->setLicensee(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
            $group->addLicensee($this);
        }

        return $this;
    }

    public function removeGroup(Group $group): self
    {
        if ($this->groups->removeElement($group)) {
            $group->removeLicensee($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, PracticeAdvice>
     */
    public function getPracticeAdvices(): Collection
    {
        return $this->practiceAdvices;
    }

    public function addPracticeAdvice(PracticeAdvice $practiceAdvice): self
    {
        if (!$this->practiceAdvices->contains($practiceAdvice)) {
            $this->practiceAdvices[] = $practiceAdvice;
            $practiceAdvice->setLicensee($this);
        }

        return $this;
    }

    public function removePracticeAdvice(PracticeAdvice $practiceAdvice): self
    {
        if ($this->practiceAdvices->removeElement($practiceAdvice)) {
            // set the owning side to null (unless already changed)
            if ($practiceAdvice->getLicensee() === $this) {
                $practiceAdvice->setLicensee(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PracticeAdvice>
     */
    public function getGivenPracticeAdvices(): Collection
    {
        return $this->givenPracticeAdvices;
    }

    public function addGivenPracticeAdvice(
        PracticeAdvice $givenPracticeAdvice,
    ): self {
        if (!$this->givenPracticeAdvices->contains($givenPracticeAdvice)) {
            $this->givenPracticeAdvices[] = $givenPracticeAdvice;
            $givenPracticeAdvice->setAuthor($this);
        }

        return $this;
    }

    public function removeGivenPracticeAdvice(
        PracticeAdvice $givenPracticeAdvice,
    ): self {
        if ($this->givenPracticeAdvices->removeElement($givenPracticeAdvice)) {
            // set the owning side to null (unless already changed)
            if ($givenPracticeAdvice->getAuthor() === $this) {
                $givenPracticeAdvice->setAuthor(null);
            }
        }

        return $this;
    }

    public function mergeWith(self $licensee): SyncReturnValues
    {
        $syncResult = ObjectComparator::equal($this, $licensee) ? SyncReturnValues::UNTOUCHED : SyncReturnValues::UPDATED;

        $this->setGender($licensee->getGender());
        $this->setLastname($licensee->getLastname());
        $this->setFirstname($licensee->getFirstname());
        $this->setBirthdate($licensee->getBirthdate());
        $this->setFftaMemberCode($licensee->getFftaMemberCode());
        $this->setFftaId($licensee->getFftaId());

        return $syncResult;
    }

    /**
     * @return Collection<int, LicenseeAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(LicenseeAttachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setLicensee($this);
        }

        return $this;
    }

    public function removeAttachment(LicenseeAttachment $attachment): self
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getLicensee() === $this) {
                $attachment->setLicensee(null);
            }
        }

        return $this;
    }

    public function hasProfilePicture(): bool
    {
        foreach ($this->getAttachments() as $attachment) {
            if (LicenseeAttachmentType::PROFILE_PICTURE === $attachment->getType()) {
                return true;
            }
        }

        return false;
    }

    public function getProfilePicture(): ?LicenseeAttachment
    {
        foreach ($this->getAttachments() as $attachment) {
            if (LicenseeAttachmentType::PROFILE_PICTURE === $attachment->getType()) {
                return $attachment;
            }
        }

        return null;
    }

    /**
     * @return ArrayCollection<int, Club>
     */
    public function getClubs(): ArrayCollection
    {
        if (null === !$this->clubs) {
            $this->clubs = new ArrayCollection();
            foreach ($this->getLicenses() as $license) {
                $club = $license->getClub();
                if (!$this->clubs->contains($club)) {
                    $this->clubs->add($club);
                }
            }
        }

        return $this->clubs;
    }
}
