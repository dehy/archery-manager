<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\Repository\ResultRepository;
use App\Scrapper\CategoryParser;
use App\Scrapper\FftaResult;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ResultRepository::class)]
#[ApiResource]
class Result
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private Licensee $licensee;

    #[ORM\ManyToOne(targetEntity: ContestEvent::class, inversedBy: 'results')]
    private ContestEvent $event;

    #[ORM\Column(type: 'DisciplineType')]
    private string $discipline;

    #[ORM\Column(type: 'LicenseAgeCategoryType')]
    private string $ageCategory;

    #[ORM\Column(type: 'LicenseActivityType')]
    private string $activity;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $distance;

    #[ORM\Column(type: 'TargetTypeType')]
    private string $targetType;

    #[ORM\Column(type: 'integer')]
    private int $targetSize;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(300)]
    private ?int $score1 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(300)]
    private ?int $score2 = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(600)]
    private int $total;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(60)]
    private ?int $nb10 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(60)]
    private ?int $nb10p = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\GreaterThanOrEqual(1)]
    private ?int $position = null;

    public static function fromFftaResult(
        FftaResult $fftaResult,
        Event $event,
        Licensee $licensee,
        string $discipline,
    ): self {
        [$ageCategory, $activity] = CategoryParser::parseString(
            $fftaResult->getCategory(),
        );

        return (new self())
            ->setEvent($event)
            ->setLicensee($licensee)
            ->setDiscipline($discipline)
            ->setActivity($activity)
            ->setScore1($fftaResult->getScore1())
            ->setScore2($fftaResult->getScore2())
            ->setTotal($fftaResult->getTotal())
            ->setNb10($fftaResult->getNb10())
            ->setNb10p($fftaResult->getNb10p());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLicensee(): ?Licensee
    {
        return $this->licensee;
    }

    public function setLicensee(?Licensee $licensee): self
    {
        $this->licensee = $licensee;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(ContestEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getDiscipline(): string
    {
        return $this->discipline;
    }

    public function setDiscipline(string $discipline): self
    {
        DisciplineType::assertValidChoice($discipline);
        $this->discipline = $discipline;

        return $this;
    }

    public function getAgeCategory(): string
    {
        return $this->ageCategory;
    }

    public function setAgeCategory(string $ageCategory): self
    {
        LicenseAgeCategoryType::assertValidChoice($ageCategory);
        $this->ageCategory = $ageCategory;

        return $this;
    }

    public function getActivity(): string
    {
        return $this->activity;
    }

    public function setActivity(string $activity): self
    {
        LicenseActivityType::assertValidChoice($activity);
        $this->activity = $activity;

        return $this;
    }

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(?int $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function setTargetType(string $targetType): self
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getTargetSize(): ?int
    {
        return $this->targetSize;
    }

    public function setTargetSize(int $targetSize): self
    {
        $this->targetSize = $targetSize;

        return $this;
    }

    public function getScore1(): ?int
    {
        return $this->score1;
    }

    public function setScore1(?int $score1): self
    {
        $this->score1 = $score1;

        return $this;
    }

    public function getScore2(): ?int
    {
        return $this->score2;
    }

    public function setScore2(?int $score2): self
    {
        $this->score2 = $score2;

        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getNb10(): ?int
    {
        return $this->nb10;
    }

    public function setNb10(?int $nb10): self
    {
        $this->nb10 = $nb10;

        return $this;
    }

    public function getNb10p(): ?int
    {
        return $this->nb10p;
    }

    public function setNb10p(?int $nb10p): self
    {
        $this->nb10p = $nb10p;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return array<int>
     */
    public static function distanceForContestAndActivity(
        ContestEvent $contestEvent,
        string $activity,
        string $ageCategory,
    ): array {
        LicenseActivityType::assertValidChoice($activity);
        LicenseAgeCategoryType::assertValidChoice($ageCategory);

        if (HobbyContestEvent::class === $contestEvent::class) {
            return self::distanceAndSizeForChallenge33(
                $contestEvent->getDiscipline(),
                $activity,
                $ageCategory,
            );
        }
        if (ContestEvent::class === $contestEvent::class) {
            return self::distanceAndSizeForFederal(
                $contestEvent->getDiscipline(),
                $activity,
                $ageCategory,
            );
        }

        dd($contestEvent, $activity, $ageCategory);
        throw new \LogicException();
    }

    public function getMaxTotal(): int
    {
        if (ContestEvent::class === $this->getEvent()::class) {
            return match ($this->getDiscipline()) {
                DisciplineType::INDOOR => 600,
                DisciplineType::TARGET => 720,
            };
        }
        if (HobbyContestEvent::class === $this->getEvent()::class) {
            return match ($this->getDiscipline()) {
                DisciplineType::INDOOR => 300,
                DisciplineType::TARGET => 360,
            };
        }
        throw new \LogicException(sprintf('Unknown total for %s - %s', $this->getEvent()::class, $this->getDiscipline()));
    }

    private static function distanceAndSizeForChallenge33(
        string $discipline,
        string $activity,
        string $ageCategory,
    ): array {
        if (DisciplineType::INDOOR === $discipline) {
            if (LicenseActivityType::CO === $activity) {
                return [18, 60];
            }

            return match ($ageCategory) {
                LicenseAgeCategoryType::POUSSIN, LicenseAgeCategoryType::U11 => [10, 80],
                LicenseAgeCategoryType::BENJAMIN, LicenseAgeCategoryType::U13 => [15, 80],
                LicenseAgeCategoryType::MINIME, LicenseAgeCategoryType::U15 => [15, 60],
                default => [18, 60],
            };
        }
        if (DisciplineType::TARGET === $discipline) {
            if (LicenseActivityType::CO === $activity) {
                return [30, 80];
            }

            return match ($ageCategory) {
                LicenseAgeCategoryType::POUSSIN, LicenseAgeCategoryType::U11, => [15, 80],
                LicenseAgeCategoryType::BENJAMIN, LicenseAgeCategoryType::U13 => [20, 122],
                LicenseAgeCategoryType::MINIME, LicenseAgeCategoryType::U15 => [25, 122],
                default => [30, 80],
            };
        }

        throw new \LogicException("Missing handling of discipline {$discipline}");
    }

    private static function distanceAndSizeForFederal(
        string $discipline,
        string $activity,
        string $ageCategory,
    ): array {
        if (DisciplineType::INDOOR === $discipline) {
            if (LicenseActivityType::CO === $activity) {
                return [18, 20];
            }

            return match ($ageCategory) {
                LicenseAgeCategoryType::POUSSIN => [18, 80],
                LicenseAgeCategoryType::BENJAMIN,
                LicenseAgeCategoryType::MINIME => [18, 60],
                default => [18, 40],
            };
        }
        if (DisciplineType::TARGET === $discipline) {
            if (LicenseActivityType::CO === $activity) {
                return [50, 80];
            }

            return match ($ageCategory) {
                LicenseAgeCategoryType::POUSSIN => [20, 80],
                LicenseAgeCategoryType::BENJAMIN => [30, 80],
                LicenseAgeCategoryType::MINIME => [40, 80],
                LicenseAgeCategoryType::CADET,
                LicenseAgeCategoryType::SENIOR_3 => [60, 122],
                LicenseAgeCategoryType::JUNIOR,
                LicenseAgeCategoryType::SENIOR_1,
                LicenseAgeCategoryType::SENIOR_2 => [70, 122],
            };
        }

        throw new \LogicException("Missing handling of discipline {$discipline}");
    }
}
