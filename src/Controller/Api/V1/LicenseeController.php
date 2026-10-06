<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\V1\LicenseePresenter;
use App\Api\V1\MemberContext;
use App\Api\V1\PrivateJson;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Security\Voter\LicenseeAccessVoter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * A licensee's profile, profile picture and attachments. Every one of them is authorized on each
 * request (see LicenseeAccessVoter) and streamed by the API: there are no long-lived public links.
 */
final readonly class LicenseeController
{
    private const array INLINE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];

    public function __construct(
        private MemberContext $context,
        private AuthorizationCheckerInterface $authorizationChecker,
        private LicenseePresenter $presenter,
        private FilesystemOperator $licenseesStorage,
    ) {
    }

    #[Route('/api/v1/licensees/{id}', name: 'api_v1_licensee', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Licensee $licensee): JsonResponse
    {
        $this->denyUnlessGranted(LicenseeAccessVoter::VIEW, $licensee);

        return PrivateJson::response(['licensee' => $this->presenter->profile($licensee, $this->context->season())]);
    }

    #[Route('/api/v1/licensees/{id}/picture', name: 'api_v1_licensee_picture', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function picture(Licensee $licensee, Request $request): Response
    {
        $this->denyUnlessGranted(LicenseeAccessVoter::VIEW_PICTURE, $licensee);

        return $this->stream($licensee->getProfilePicture(), $request, false);
    }

    #[Route('/api/v1/licensees/{id}/attachments/{attachmentId}', name: 'api_v1_licensee_attachment', requirements: ['id' => '\d+', 'attachmentId' => '\d+'], methods: ['GET'])]
    public function attachment(Licensee $licensee, int $attachmentId, Request $request): Response
    {
        $this->denyUnlessGranted(LicenseeAccessVoter::VIEW, $licensee);

        $attachment = $licensee->getAttachments()->findFirst(static fn (int $key, LicenseeAttachment $a): bool => $a->getId() === $attachmentId);

        return $this->stream($attachment, $request, $request->query->getBoolean('download'));
    }

    private function denyUnlessGranted(string $attribute, Licensee $licensee): void
    {
        if (!$this->authorizationChecker->isGranted($attribute, $licensee)) {
            throw new AccessDeniedHttpException('You are not allowed to access this licensee.');
        }
    }

    private function stream(?LicenseeAttachment $attachment, Request $request, bool $download): Response
    {
        $file = $attachment?->getFile();
        $name = $file?->getName();
        if (null === $name) {
            throw new NotFoundHttpException('No such file.');
        }

        // The stored file is the source of truth for its size and modification time. The entity's
        // updatedAt is not usable as a validator: Vich's setUploadedFile() resets it to "now" every
        // time an attachment is loaded. A missing file makes both calls throw.
        try {
            $lastModified = new \DateTimeImmutable()->setTimestamp($this->licenseesStorage->lastModified($name));
            $size = $this->licenseesStorage->fileSize($name);
        } catch (FilesystemException) {
            throw new NotFoundHttpException('No such file.');
        }

        // Conditional requests: the mobile app revalidates a cached picture instead of downloading it again.
        $notModified = new Response();
        $this->makeRevalidated($notModified, $lastModified);
        if ($notModified->isNotModified($request)) {
            return $notModified;
        }

        $storage = $this->licenseesStorage;
        $response = new StreamedResponse(static function () use ($storage, $name): void {
            $input = $storage->readStream($name);
            stream_copy_to_stream($input, fopen('php://output', 'wb'));
            fclose($input);
        });
        $mimeType = $file?->getMimeType() ?? 'application/octet-stream';
        $fileName = $file?->getOriginalName() ?? basename($name);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', (string) $size);
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            $download || !$this->isSafeToDisplayInline($mimeType) ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
            $fileName,
            // The ASCII-only fallback some clients use: no non-ASCII characters, '%', or path separators.
            (string) preg_replace('/[^\x20-\x7e]|[%\/\\\\]/u', '_', $fileName),
        ));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $this->makeRevalidated($response, $lastModified);

        return $response;
    }

    /**
     * Private (it depends on who asks), and checked against the server on every use: authorization can
     * change, and a replaced picture keeps the same URL.
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
