<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\V1\FileStreamer;
use App\Api\V1\MemberContext;
use App\Api\V1\PracticeAdvicePresenter;
use App\Api\V1\PrivateJson;
use App\Entity\Licensee;
use App\Entity\PracticeAdvice;
use App\Entity\PracticeAdviceAttachment;
use App\Repository\PracticeAdviceRepository;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The practice advice a coach wrote for the selected licensee. Advice is personal: like on the web, it is
 * only found by the licensee it was written for (anybody else gets a 404, as if it did not exist).
 */
final readonly class PracticeAdviceController
{
    public function __construct(
        private MemberContext $context,
        private PracticeAdviceRepository $advices,
        private PracticeAdvicePresenter $presenter,
        private FileStreamer $fileStreamer,
        private FilesystemOperator $licenseesStorage,
    ) {
    }

    /**
     * Newest first. Archived advice is left out unless `archived=true`.
     */
    #[Route('/api/v1/practice-advices', name: 'api_v1_practice_advices', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->context->requireLicense();
        $advices = $this->advices->findForLicenseeNewestFirst($this->licensee(), $request->query->getBoolean('archived'));
        $counts = $this->advices->countAttachments($advices);

        return PrivateJson::response(['advices' => array_map(
            fn (PracticeAdvice $advice): array => $this->presenter->summary($advice, $counts[$advice->getId()] ?? 0),
            $advices,
        )]);
    }

    #[Route('/api/v1/practice-advices/{id}', name: 'api_v1_practice_advice', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(PracticeAdvice $advice): JsonResponse
    {
        $this->context->requireLicense();
        $this->assertGivenToSelectedLicensee($advice);

        return PrivateJson::response(['advice' => $this->presenter->detail($advice, $this->advices->attachmentsOf($advice))]);
    }

    #[Route('/api/v1/practice-advices/{id}/attachments/{attachmentId}', name: 'api_v1_practice_advice_attachment', requirements: ['id' => '\d+', 'attachmentId' => '\d+'], methods: ['GET'])]
    public function attachment(PracticeAdvice $advice, int $attachmentId, Request $request): Response
    {
        $this->context->requireLicense();
        $this->assertGivenToSelectedLicensee($advice);

        $attachment = array_find(
            $this->advices->attachmentsOf($advice),
            static fn (PracticeAdviceAttachment $candidate): bool => $candidate->getId() === $attachmentId,
        );

        return $this->fileStreamer->stream($this->licenseesStorage, $attachment?->getFile(), $request, $request->query->getBoolean('download'));
    }

    private function licensee(): Licensee
    {
        return $this->context->licensee() ?? throw new AccessDeniedHttpException('No licensee selected.');
    }

    private function assertGivenToSelectedLicensee(PracticeAdvice $advice): void
    {
        if ($advice->getLicensee()?->getId() !== $this->licensee()->getId()) {
            throw new NotFoundHttpException('No such advice.');
        }
    }
}
