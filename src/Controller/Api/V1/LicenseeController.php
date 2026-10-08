<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\V1\FileStreamer;
use App\Api\V1\LicenseePresenter;
use App\Api\V1\MemberContext;
use App\Api\V1\PrivateJson;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Security\Voter\LicenseeAccessVoter;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        private FileStreamer $fileStreamer,
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

        return $this->fileStreamer->stream($this->licenseesStorage, $licensee->getProfilePicture()?->getFile(), $request, false);
    }

    #[Route('/api/v1/licensees/{id}/attachments/{attachmentId}', name: 'api_v1_licensee_attachment', requirements: ['id' => '\d+', 'attachmentId' => '\d+'], methods: ['GET'])]
    public function attachment(Licensee $licensee, int $attachmentId, Request $request): Response
    {
        $this->denyUnlessGranted(LicenseeAccessVoter::VIEW, $licensee);

        $attachment = $licensee->getAttachments()->findFirst(static fn (int $key, LicenseeAttachment $a): bool => $a->getId() === $attachmentId);

        return $this->fileStreamer->stream($this->licenseesStorage, $attachment?->getFile(), $request, $request->query->getBoolean('download'));
    }

    private function denyUnlessGranted(string $attribute, Licensee $licensee): void
    {
        if (!$this->authorizationChecker->isGranted($attribute, $licensee)) {
            throw new AccessDeniedHttpException('You are not allowed to access this licensee.');
        }
    }
}
