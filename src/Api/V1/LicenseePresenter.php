<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseeAttachmentType;
use App\DBAL\Types\LicenseType;
use App\Entity\Club;
use App\Entity\Group;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Twig\LicenseeDisplayExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class LicenseePresenter
{
    public function __construct(
        private LicenseeDisplayExtension $displayName,
        private ClubPresenter $clubPresenter,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * The name the viewer is allowed to see: the web rule, full names for admins and coaches
     * and "Firstname L." for everybody else.
     */
    public function displayName(Licensee $licensee): string
    {
        return $this->displayName->getLicenseeDisplayName($licensee);
    }

    /**
     * What the member directory shows of a member.
     *
     * Groups are those of $club only: a member who changed club still carries the groups of the old
     * one, which must not show up (nor be counted) in the new club. `null` keeps all the groups.
     * $hasPicture saves a lookup per member when the caller already knows it for a whole page.
     *
     * @return array<string, mixed>
     */
    public function summary(Licensee $licensee, int $season, ?Club $club = null, ?bool $hasPicture = null): array
    {
        return [
            'id' => $licensee->getId(),
            'display_name' => $this->displayName($licensee),
            'groups' => array_map($this->clubPresenter->groupReference(...), $this->groupsOf($licensee, $club)),
            'activities' => EnumValue::listOf(LicenseActivityType::class, $licensee->getLicenseForSeason($season)?->getActivities()),
            'picture_url' => ($hasPicture ?? $licensee->hasProfilePicture()) ? $this->pictureUrl($licensee) : null,
        ];
    }

    /**
     * The groups of a licensee that belong to $club (all of them when $club is null).
     *
     * @return list<Group>
     */
    public function groupsOf(Licensee $licensee, ?Club $club): array
    {
        $groups = [];
        foreach ($licensee->getGroups() as $group) {
            if (!$club instanceof Club || $group->getClub() === $club) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    /**
     * The profile page, for those allowed to see it (see LicenseeAccessVoter::VIEW).
     *
     * @return array<string, mixed>
     */
    public function profile(Licensee $licensee, int $season): array
    {
        return [
            ...$this->summary($licensee, $season, $licensee->getLicenseForSeason($season)?->getClub()),
            'firstname' => $licensee->getFirstname(),
            'lastname' => $licensee->getLastname(),
            'full_name' => $licensee->getFullname(),
            'gender' => EnumValue::of(GenderType::class, $licensee->getGender()),
            'ffta_member_code' => $licensee->getFftaMemberCode(),
            'license' => $this->license($licensee->getLicenseForSeason($season)),
            'attachments' => array_values(array_map($this->attachment(...), $licensee->getAttachments()->toArray())),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function license(?License $license): ?array
    {
        if (!$license instanceof License) {
            return null;
        }

        $club = $license->getClub();

        return [
            'season' => $license->getSeason(),
            'club' => $club instanceof \App\Entity\Club ? $this->clubPresenter->reference($club) : null,
            'type' => EnumValue::of(LicenseType::class, $this->stringOrNull($license->getType())),
            'category' => EnumValue::of(LicenseCategoryType::class, $this->stringOrNull($license->getCategory())),
            'age_category' => EnumValue::of(LicenseAgeCategoryType::class, $this->stringOrNull($license->getAgeCategory())),
            'activities' => EnumValue::listOf(LicenseActivityType::class, $license->getActivities()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attachment(LicenseeAttachment $attachment): array
    {
        $file = $attachment->getFile();

        return [
            'id' => $attachment->getId(),
            'type' => EnumValue::of(LicenseeAttachmentType::class, $attachment->getType()),
            'season' => $attachment->getSeason(),
            'document_date' => $attachment->getDocumentDate()?->format('Y-m-d'),
            'file_name' => $file?->getOriginalName(),
            'mime_type' => $file?->getMimeType(),
            'size' => $file?->getSize(),
            'url' => $this->urlGenerator->generate('api_v1_licensee_attachment', [
                'id' => $attachment->getLicensee()?->getId(),
                'attachmentId' => $attachment->getId(),
            ]),
        ];
    }

    private function pictureUrl(Licensee $licensee): string
    {
        return $this->urlGenerator->generate('api_v1_licensee_picture', ['id' => $licensee->getId()]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return \is_string($value) ? $value : null;
    }
}
