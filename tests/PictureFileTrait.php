<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Real image files for upload tests, generated on the fly with GD.
 */
trait PictureFileTrait
{
    /**
     * Path of a temporary PNG of the given size.
     */
    private static function pngPath(int $width = 120, int $height = 120): string
    {
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 30, 120, 200));

        $path = tempnam(sys_get_temp_dir(), 'picture_').'.png';
        imagepng($image, $path);

        return $path;
    }

    private static function uploadedPng(int $width = 120, int $height = 120): UploadedFile
    {
        return new UploadedFile(self::pngPath($width, $height), 'photo.png', 'image/png', null, true);
    }

    private static function uploadedText(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'not_a_picture_');
        file_put_contents($path, 'definitely not an image');

        return new UploadedFile($path, 'photo.png', 'image/png', null, true);
    }
}
