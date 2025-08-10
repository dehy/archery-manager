<?php

namespace App\Story;

use App\Entity\Licensee;
use App\Factory\ClubFactory;
use App\Factory\LicenseeFactory;
use App\Factory\LicenseFactory;
use Zenstruck\Foundry\Story;

final class DefaultLicenseesStory extends Story
{
    public function build(): void
    {
        LicenseeFactory::createMany(150, function (Licensee $licensee) {
            $license = LicenseFactory::new()
                ->with([
                    'licensee' => $licensee,
                    'club' => ClubFactory::random(),
                ]);

            return ['licenses' => [$license]];
        });
    }
}
