<?php

namespace App\Story;

use App\Factory\ClubFactory;
use Zenstruck\Foundry\Story;

final class DefaultClubsStory extends Story
{
    public function build(): void
    {
        ClubFactory::createMany(4);
    }
}
