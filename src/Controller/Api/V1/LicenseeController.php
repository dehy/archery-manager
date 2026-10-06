<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\V1\LicenseePresenter;
use App\Api\V1\MemberContext;
use App\Api\V1\PrivateJson;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Security\Voter\LicenseeAccessVoter;
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
        if (!$attachment instanceof \App\Entity\LicenseeAttachment || null === $name || !$this->licenseesStorage->fileExists($name)) {
            throw new NotFoundHttpException('No such file.');
        }

        // Conditional requests: the mobile app can keep pictures without downloading them again.
        // The validator is the stored file's own modification time: the entity's updatedAt is not
        // usable, Vich's setUploadedFile() resets it to "now" every time the attachment is loaded.
        $lastModified = new \DateTimeImmutable()->setTimestamp($this->licenseesStorage->lastModified($name));
        $notModified = new Response();
        $notModified->setPrivate();
        $notModified->setLastModified($lastModified);
        if ($notModified->isNotModified($request)) {
            return $notModified;
        }

        $storage = $this->licenseesStorage;
        $response = new StreamedResponse(static function () use ($storage, $name): void {
            $input = $storage->readStream($name);
            stream_copy_to_stream($input, fopen('php://output', 'wb'));
            fclose($input);
        });
        $response->headers->set('Content-Type', $file?->getMimeType() ?? 'application/octet-stream');
        $response->headers->set('Content-Length', (string) ($file?->getSize() ?? $storage->fileSize($name)));

        $fileName = $file?->getOriginalName() ?? basename($name);
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            $download ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
            $fileName,
            // The ASCII-only fallback some clients use: no non-ASCII characters, '%', or path separators.
            (string) preg_replace('/[^\x20-\x7e]|[%\/\\\\]/u', '_', $fileName),
        ));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setPrivate();
        $response->setLastModified($lastModified);

        return $response;
    }
}
