<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

/**
 * The file description an attachment embeds, for fixtures. The file itself is a sample of fixtures/files,
 * which `make fixtures` copies into the local storage.
 */
final class EmbeddedFileProvider extends Base
{
    public static function embeddedFile(string $name, string $originalName, string $mimeType, int $size): EmbeddedFile
    {
        $file = new EmbeddedFile();
        $file->setName($name);
        $file->setOriginalName($originalName);
        $file->setMimeType($mimeType);
        $file->setSize($size);

        return $file;
    }
}
