<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\DBAL\Types\PracticeAdviceAttachmentType;
use App\Entity\PracticeAdvice;
use App\Entity\PracticeAdviceAttachment;
use App\Service\PracticeAdviceRenderer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Practice advice: the advice a coach wrote for a member. The text is Markdown; it is sent both as written
 * and rendered to sanitized HTML (see PracticeAdviceRenderer), so a client can display either.
 */
final readonly class PracticeAdvicePresenter
{
    public function __construct(
        private PracticeAdviceRenderer $renderer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(PracticeAdvice $advice, int $attachmentsCount): array
    {
        return [
            'id' => $advice->getId(),
            'title' => $advice->getTitle(),
            'author_firstname' => $advice->getAuthor()?->getFirstname(),
            'created_at' => $advice->getCreatedAt()?->format(\DATE_ATOM),
            'archived_at' => $advice->getArchivedAt()?->format(\DATE_ATOM),
            'attachments_count' => $attachmentsCount,
        ];
    }

    /**
     * @param list<PracticeAdviceAttachment> $attachments
     *
     * @return array<string, mixed>
     */
    public function detail(PracticeAdvice $advice, array $attachments): array
    {
        return [
            ...$this->summary($advice, \count($attachments)),
            'advice_markdown' => (string) $advice->getAdvice(),
            'advice_html' => $this->renderer->toHtml($advice->getAdvice()),
            'attachments' => array_map(fn (PracticeAdviceAttachment $attachment): array => $this->attachment($advice, $attachment), $attachments),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attachment(PracticeAdvice $advice, PracticeAdviceAttachment $attachment): array
    {
        $file = $attachment->getFile();

        return [
            'id' => $attachment->getId(),
            'type' => EnumValue::of(PracticeAdviceAttachmentType::class, $attachment->getType()),
            'file_name' => $file?->getOriginalName(),
            'mime_type' => $file?->getMimeType(),
            'size' => $file?->getSize(),
            'url' => $this->urlGenerator->generate('api_v1_practice_advice_attachment', [
                'id' => $advice->getId(),
                'attachmentId' => $attachment->getId(),
            ]),
        ];
    }
}
