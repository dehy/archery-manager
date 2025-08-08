<?php

namespace App\Story;

use App\Factory\EventFactory;
use Zenstruck\Foundry\Story;

final class DefaultEventsStory extends Story
{
    public function build(): void
    {
        EventFactory::createMany(15);
    }
}
