<?php

declare(strict_types=1);

namespace App\Tests;

use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;

class FakePublicUrlGenerator implements PublicUrlGenerator, TemporaryUrlGenerator
{
    #[\Override]
    public function publicUrl(string $path, Config $config): string
    {
        return $path;
    }

    #[\Override]
    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config): string
    {
        return $path;
    }
}
