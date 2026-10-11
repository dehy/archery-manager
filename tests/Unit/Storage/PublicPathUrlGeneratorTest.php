<?php

declare(strict_types=1);

namespace App\Tests\Unit\Storage;

use App\Storage\PublicPathUrlGenerator;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;

final class PublicPathUrlGeneratorTest extends TestCase
{
    /**
     * Wired like the `when@dev` storages: a local adapter whose files are served from public/uploads.
     */
    public function testALocalStorageGivesThePathUnderUploadsAsPublicAndTemporaryUrl(): void
    {
        $generator = new PublicPathUrlGenerator('/uploads/licensees/');
        $storage = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir()), [], null, $generator, $generator);

        $this->assertSame('/uploads/licensees/advice/plan.pdf', $storage->publicUrl('advice/plan.pdf'));
        $this->assertSame('/uploads/licensees/advice/plan.pdf', $storage->temporaryUrl('/advice/plan.pdf', new \DateTimeImmutable('+10 minutes')));
    }
}
