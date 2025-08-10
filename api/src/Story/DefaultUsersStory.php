<?php

namespace App\Story;

use App\Factory\LicenseeFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class DefaultUsersStory extends Story
{
    public function build(): void
    {
        UserFactory::createMany(150, function () {
            if (random_int(0, 100) > 90) {
                return ['licensees' => [LicenseeFactory::new()]];
            }

            return [];
        });
    }
}
