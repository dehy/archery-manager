<?php

namespace App\DataFixtures;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventType;
use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class EventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr');

        $parisTimezone = new \DateTimeZone('Europe/Paris');
        $saturday = new \DateTimeImmutable('next saturday midnight', $parisTimezone);
        $sunday = new \DateTimeImmutable('next sunday midnight', $parisTimezone);

        for ($i = 1; $i <= 10; ++$i) {
            $saturday = $saturday->add(new \DateInterval('P7D'));
            $sunday = $sunday->add(new \DateInterval('P7D'));

            $event = new Event();
            $event
                ->setName($faker->city())
                ->setType(EventType::CONTEST_OFFICIAL)
                ->setStartsAt($saturday)
                ->setEndsAt($sunday)
                ->setAllDay(true)
                ->setAddress($faker->address())
                ->setContestType(ContestType::FEDERAL)
                ->setDiscipline(DisciplineType::INDOOR)
            ;
            $manager->persist($event);
        }

        $manager->flush();
    }
}
