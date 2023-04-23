<?php

namespace App\DataFixtures;

use App\Entity\Club;
use App\Entity\Group;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class GroupFixtures extends Fixture implements DependentFixtureInterface
{
    public const DEFAULT_GROUP_1 = 'default_group_1';

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
        $groupsDatas = [
            'Groupe 1',
            'Groupe 2',
            'Groupe 3',
        ];

        foreach ($groupsDatas as $groupData) {
            $group = new Group();
            $group->setClub($defaultClub);
            $group->setName($groupData);

            $manager->persist($group);
        }

        $manager->flush();

        $manager->flush();
    }
}
