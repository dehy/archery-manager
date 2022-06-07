<?php

namespace App\Entity;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\Repository\ResultRepository;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity(repositoryClass: ResultRepository::class)]
class Result
{
    private $distanceMatrice = [
        ContestType::CHALLENGE33 => [],
        ContestType::FEDERAL => [],
        ContestType::INTERNATIONAL => [],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: Licensee::class, inversedBy: "results")]
    #[ORM\JoinColumn(nullable: false)]
    private $licensee;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: "results")]
    private $event;

    #[ORM\Column(type: "DisciplineType")]
    private $discipline;

    #[ORM\Column(type: "integer", nullable: true)]
    private $distance;

    #[ORM\Column(type: "integer")]
    private $score;

    #[ORM\Column(type: "LicenseActivityType")]
    private $activity;

    #[ORM\Column(type: 'integer')]
    private $targetSize;

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

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getDiscipline()
    {
        return $this->discipline;
    }

    public function setDiscipline($discipline): self
    {
        $this->discipline = $discipline;

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

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getActivity()
    {
        return $this->activity;
    }

    public function setActivity($activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @param string $discipline
     * @param string $ageCategory
     * @param string $contestType
     * @param string $activity
     * @return array<int>
     */
    public static function distanceForContestTypeAndActivity(
        string $contestType,
        string $discipline,
        string $activity,
        string $ageCategory
    ): array {
        if ($contestType === ContestType::CHALLENGE33) {
            return self::distanceForChallenge33(
                $discipline,
                $activity,
                $ageCategory
            );
        }

        throw new LogicException();
    }

    private static function distanceForChallenge33(
        string $discipline,
        string $activity,
        string $ageCategory
    ) {
        if ($discipline === DisciplineType::INDOOR) {
            if ($activity === LicenseActivityType::CO) {
                return [18, 60];
            }
            switch ($ageCategory) {
                case LicenseAgeCategoryType::POUSSIN:
                    return [10, 80];
                case LicenseAgeCategoryType::BENJAMIN:
                    return [15, 80];
                case LicenseAgeCategoryType::MINIME:
                    return [15, 60];
                default:
                    return [18, 60];
            }
        }
        if ($discipline === DisciplineType::TARGET) {
            if ($activity === LicenseActivityType::CO) {
                return [30, 80];
            }
            switch ($ageCategory) {
                case LicenseAgeCategoryType::POUSSIN:
                case LicenseAgeCategoryType::BENJAMIN:
                    return [15, 80];
                case LicenseAgeCategoryType::MINIME:
                    return [25, 80];
                default:
                    return [30, 122];
            }
        }
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
}
