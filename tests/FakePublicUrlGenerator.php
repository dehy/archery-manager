<?php

namespace App\Tests;

use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;

class FakePublicUrlGenerator implements PublicUrlGenerator
{
    public function publicUrl(string $path, Config $config): string
    {
        return $path;
    }
}
