<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\V1\FileStreamer;
use App\Entity\PracticeAdvice;
use App\Entity\PracticeAdviceAttachment;
use App\Entity\User;
use App\Helper\LicenseeHelper;
use App\Repository\PracticeAdviceRepository;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Practice advice is personal: only the archer it was written for reads it (a 404 for anybody else, as in the API).
 */
class PracticeAdviceController extends AbstractController
{
    public function __construct(
        private readonly LicenseeHelper $licenseeHelper,
        private readonly PracticeAdviceRepository $practiceAdviceRepository,
        private readonly FileStreamer $fileStreamer,
        private readonly FilesystemOperator $licenseesStorage,
    ) {
    }

    /** The archer's advice, newest first, then the archived ones (redesigned interface; today's lists it nowhere). */
    #[Route('/practice-advices', name: 'app_practice_advice_index')]
    public function index(): Response
    {
        if (!$this->hasNewInterface()) {
            return $this->redirectToRoute('app_licensee_my_profile');
        }

        $licensee = $this->licenseeHelper->getLicenseeFromSession();
        $all = $this->practiceAdviceRepository->findForLicenseeNewestFirst($licensee, true);

        return $this->render('practice_advice/index_next.html.twig', [
            'current' => array_values(array_filter($all, static fn (PracticeAdvice $advice): bool => !$advice->getArchivedAt() instanceof \DateTimeImmutable)),
            'archived' => array_values(array_filter($all, static fn (PracticeAdvice $advice): bool => $advice->getArchivedAt() instanceof \DateTimeImmutable)),
            'attachment_counts' => $this->practiceAdviceRepository->countAttachments($all),
        ]);
    }

    #[Route('/practice-advices/{advice}', name: 'app_practice_advice_show', requirements: ['advice' => '\d+'])]
    public function show(PracticeAdvice $advice): Response
    {
        $this->assertOwnAdvice($advice);

        if ($this->hasNewInterface()) {
            return $this->render('practice_advice/show_next.html.twig', [
                'advice' => $advice,
                'attachments' => $this->practiceAdviceRepository->attachmentsOf($advice),
            ]);
        }

        return $this->render('practice_advice/show.html.twig', [
            'advice' => $advice,
        ]);
    }

    #[Route('/practice-advices/{advice}/attachments/{attachmentId}', name: 'app_practice_advice_attachment', requirements: ['advice' => '\d+', 'attachmentId' => '\d+'])]
    public function attachment(PracticeAdvice $advice, int $attachmentId, Request $request): Response
    {
        $this->assertOwnAdvice($advice);

        $attachment = array_find(
            $this->practiceAdviceRepository->attachmentsOf($advice),
            static fn (PracticeAdviceAttachment $candidate): bool => $candidate->getId() === $attachmentId,
        );

        return $this->fileStreamer->stream($this->licenseesStorage, $attachment?->getFile(), $request, $request->query->getBoolean('download'));
    }

    private function assertOwnAdvice(PracticeAdvice $advice): void
    {
        if ($advice->getLicensee()->getId() !== $this->licenseeHelper->getLicenseeFromSession()->getId()) {
            throw $this->createNotFoundException();
        }
    }

    private function hasNewInterface(): bool
    {
        $user = $this->getUser();

        return $user instanceof User && $user->hasNewInterface();
    }
}
