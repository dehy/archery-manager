<?php

namespace App\DataFixtures;

use App\Entity\Club;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class ClubFixtures extends Fixture
{
    public const DEFAULT_CLUB_REFERENCE = 'default-club';
    public const NOTTINGHAM_CLUB_REFERENCE = 'nottingham-club';

    public function load(ObjectManager $manager): void
    {
        $defaultClub = new Club();
        $defaultClub->setName('Joyeux Compagnons')
            ->setCity('Forêt de Sherwood')
            ->setLogoName('joyeux-compagnons.svg')
            ->setPrimaryColor('#149800')
            ->setFftaCode('123456S')
            ->setContactEmail('contact@joyeux-compagnons.co.uk');
        $manager->persist($defaultClub);
        $this->addReference(self::DEFAULT_CLUB_REFERENCE, $defaultClub);

        $nottingham = new Club();
        $nottingham->setName('Nottingham')
            ->setCity('Nottingham')
            ->setLogoName('nottingham.svg')
            ->setPrimaryColor('#333333')
            ->setFftaCode('654321T')
            ->setContactEmail('contact@nottingham.co.uk');
        $manager->persist($nottingham);
        $this->addReference(self::NOTTINGHAM_CLUB_REFERENCE, $nottingham);

        $manager->flush();
    }
}
