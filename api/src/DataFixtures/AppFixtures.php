<?php

namespace App\DataFixtures;

use App\Story\DefaultClubsStory;
use App\Story\DefaultEventsStory;
use App\Story\DefaultUsersStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        DefaultClubsStory::load();
        DefaultUsersStory::load();
        DefaultEventsStory::load();
    }
}
