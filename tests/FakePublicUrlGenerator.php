<?php

declare(strict_types=1);

namespace App\Tests;

use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;

class FakePublicUrlGenerator implements PublicUrlGenerator
{
    #[\Override]
    public function publicUrl(string $path, Config $config): string
    {
        return $path;
    }
}
