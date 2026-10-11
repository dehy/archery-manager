<?php

declare(strict_types=1);

namespace App\Storage;

use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;

/**
 * Local development only (see the `when@dev` storages in config/packages/flysystem.yaml): the files live
 * under public/uploads and are served as static files, so both the public and the "temporary" URL of a file
 * are its path there. Never use it for a storage holding real members' documents.
 */
final readonly class PublicPathUrlGenerator implements PublicUrlGenerator, TemporaryUrlGenerator
{
    public function __construct(private string $prefix)
    {
    }

    public function publicUrl(string $path, Config $config): string
    {
        return rtrim($this->prefix, '/').'/'.ltrim($path, '/');
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config): string
    {
        return $this->publicUrl($path, $config);
    }
}
