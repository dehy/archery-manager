<?php

namespace App\Factory;

use App\Entity\Event;
use App\Entity\Licensee;
use App\Entity\Result;
use App\Scrapper\CategoryParser;
use App\Scrapper\FftaResult;

class ResultFactory
{
    public static function createFromEventLicenseeAndFftaResult(
        Event $event,
        Licensee $licensee,
        FftaResult $fftaResult,
    ): Result {
        $category = $fftaResult->getCategory();
        [$ageCategory, $activity] = CategoryParser::parseString($category);
        [$distance, $size] = Result::distanceForContestTypeAndActivity(
            $event->getContestType(),
            $event->getDiscipline(),
            $activity,
            $ageCategory,
        );

        $season = $event->getSeason();

        return (new Result())
            ->setEvent($event)
            ->setLicensee($licensee)
            ->setDiscipline($event->getDiscipline())
            ->setAgeCategory(
                $licensee->getLicenseForSeason($season)->getAgeCategory(),
            )
            ->setActivity($activity)
            ->setDistance($distance)
            ->setTargetSize($size)
            ->setScore1($fftaResult->getScore1())
            ->setScore2($fftaResult->getScore2())
            ->setTotal($fftaResult->getTotal())
            ->setNb10($fftaResult->getNb10())
            ->setNb10p($fftaResult->getNb10p())
            ->setPosition($fftaResult->getPosition())
        ;
    }
}
