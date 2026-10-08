<?php

declare(strict_types=1);

namespace App\Api\V1;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

/**
 * Streams a stored file to an authorized API client. The caller authorizes; this takes care of the HTTP side:
 * the stored file is the source of truth for size and modification time, files are revalidated on every
 * use, and only plain images and PDFs are ever displayed inline.
 */
final readonly class FileStreamer
{
    private const array INLINE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];

    /**
     * @throws NotFoundHttpException when there is no file or it is missing from the storage
     */
    public function stream(FilesystemOperator $storage, ?EmbeddedFile $file, Request $request, bool $download): Response
    {
        $name = $file?->getName();
        if (null === $name) {
            throw new NotFoundHttpException('No such file.');
        }

        // The entity's updatedAt is not usable as a validator: Vich's setUploadedFile() resets it to "now"
        // every time an attachment is loaded. A missing file makes both storage calls throw.
        try {
            $lastModified = new \DateTimeImmutable()->setTimestamp($storage->lastModified($name));
            $size = $storage->fileSize($name);
        } catch (FilesystemException) {
            throw new NotFoundHttpException('No such file.');
        }

        // Conditional requests: the mobile app revalidates a cached file instead of downloading it again.
        $notModified = new Response();
        $this->makeRevalidated($notModified, $lastModified);
        if ($notModified->isNotModified($request)) {
            return $notModified;
        }

        $response = new StreamedResponse(static function () use ($storage, $name): void {
            $input = $storage->readStream($name);
            stream_copy_to_stream($input, fopen('php://output', 'wb'));
            fclose($input);
        });
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        // The original name comes from an upload or the FFTA: it may hold path separators, which a
        // Content-Disposition header cannot carry (building it would throw).
        $fileName = str_replace(['/', '\\'], '_', $file->getOriginalName() ?? basename($name));
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', (string) $size);
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            $download || !$this->isSafeToDisplayInline($mimeType) ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
            $fileName,
            $this->asciiFallbackName($fileName),
        ));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $this->makeRevalidated($response, $lastModified);

        return $response;
    }

    /**
     * The ASCII-only file name some clients fall back to: HTTP forbids '%' and path separators in it,
     * and anything that is not printable ASCII (accents...) is replaced.
     */
    private function asciiFallbackName(string $fileName): string
    {
        return (string) preg_replace('/[^\x20-\x7e]/u', '_', str_replace(['%', '/', '\\'], '_', $fileName));
    }

    /**
     * Private (it depends on who asks), and checked against the server on every use: authorization can
     * change, and a replaced file keeps the same URL.
     */
    private function makeRevalidated(Response $response, \DateTimeImmutable $lastModified): void
    {
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->setLastModified($lastModified);
    }

    /**
     * Only plain images and PDFs may be shown inline. Anything else (an HTML or SVG file that was uploaded
     * as an attachment, for instance) is always a download.
     */
    private function isSafeToDisplayInline(string $mimeType): bool
    {
        return \in_array(strtolower(trim(explode(';', $mimeType)[0])), self::INLINE_MIME_TYPES, true);
    }
}
