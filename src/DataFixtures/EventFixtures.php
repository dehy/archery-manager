<?php

namespace App\DataFixtures;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\Entity\Club;
use App\Entity\ContestEvent;
use App\Entity\TrainingEvent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class EventFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies()
    {
        return [
            ClubFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var Club $defaultClub */
        $defaultClub = $this->getReference(ClubFixtures::DEFAULT_CLUB_REFERENCE);
        /** @var Club $nottinghamClub */
        $nottinghamClub = $this->getReference(ClubFixtures::NOTTINGHAM_CLUB_REFERENCE);

        $faker = Faker\Factory::create('fr');

        $cities = ['Bordeaux', 'Le Bouscat', 'Mérignac', 'Auros', 'Andernos', 'St Médard', 'Arsac'];

        $parisTimezone = new \DateTimeZone('Europe/Paris');
        $saturday = (new \DateTimeImmutable('this saturday', $parisTimezone))->modify('-7 days');
        $sunday = $saturday->modify('+1 day');

        for ($i = 0; $i < \count($cities); ++$i) {
            $event = new ContestEvent();
            $event
                ->setContestType(ContestType::INDIVIDUAL)
                ->setName($cities[$i])
                ->setStartsAt($saturday)
                ->setEndsAt($sunday)
                ->setAllDay(true)
                ->setAddress($faker->address())
                ->setDiscipline(DisciplineType::INDOOR);
            $manager->persist($event);

            $saturday = $saturday->add(new \DateInterval('P7D'));
            $sunday = $sunday->add(new \DateInterval('P7D'));
        }

        $manager->flush();

        $defaultClubAddress = $faker->address();
        $trainingStart = (new \DateTimeImmutable('this saturday 10am', $parisTimezone))->modify('-7 days');
        for ($i = 0; $i < 10; ++$i) {
            $trainingEnd = $trainingStart->add(new \DateInterval('PT1H'));
            $event = new TrainingEvent();
            $event
                ->setClub($defaultClub)
                ->setName('Groupe 1')
                ->setAddress($defaultClubAddress)
                ->setStartsAt($trainingStart)
                ->setEndsAt($trainingEnd)
                ->setAllDay(false)
                ->setDiscipline(DisciplineType::INDOOR)
            ;
            $manager->persist($event);

            $trainingStart = $trainingStart->add(new \DateInterval('P7D'));
        }

        $manager->flush();
    }
}
