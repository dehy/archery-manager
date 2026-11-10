<?php

declare(strict_types=1);

namespace App\Service;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Replaces or removes a licensee's profile picture by hand (web and mobile API).
 *
 * A picture set here is flagged as a manual upload, so the FFTA sync keeps it. Removing it lets the sync add the
 * FFTA picture again.
 */
final readonly class ProfilePictureManager
{
    public const array MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private ClockInterface $clock,
    ) {
    }

    /**
     * The rules an uploaded picture must follow.
     *
     * @return list<Constraint>
     */
    public static function constraints(): array
    {
        return [
            new Assert\NotNull(message: 'Choose a picture.'),
            new Assert\Image(
                maxSize: '5M',
                mimeTypes: self::MIME_TYPES,
                minWidth: 100,
                minHeight: 100,
                mimeTypesMessage: 'The picture must be a JPEG, PNG or WebP image.',
            ),
        ];
    }

    /**
     * The first rule the file breaks, or null when it is a valid picture.
     */
    public function violation(?UploadedFile $file): ?string
    {
        $violations = $this->validator->validate($file, self::constraints());

        return 0 === $violations->count() ? null : (string) $violations->get(0)->getMessage();
    }

    public function replace(Licensee $licensee, UploadedFile $file): void
    {
        $this->detachCurrent($licensee);

        $picture = new LicenseeAttachment();
        $picture
            ->setType(LicenseeAttachmentType::PROFILE_PICTURE)
            ->setManualUpload(true)
            ->setUploadedFile($file);
        $licensee->addAttachment($picture);
        $this->entityManager->persist($picture);

        $this->touchAndFlush($licensee);
    }

    public function remove(Licensee $licensee): void
    {
        $this->detachCurrent($licensee);
        $this->touchAndFlush($licensee);
    }

    private function detachCurrent(Licensee $licensee): void
    {
        $current = $licensee->getProfilePicture();
        if ($current instanceof LicenseeAttachment) {
            // orphanRemoval deletes the row; Vich (delete_on_remove) deletes the file.
            $licensee->removeAttachment($current);
            $this->entityManager->remove($current);
        }
    }

    private function touchAndFlush(Licensee $licensee): void
    {
        // Picture responses are cached with Last-Modified = the licensee's updatedAt.
        $licensee->setUpdatedAt(\DateTimeImmutable::createFromInterface($this->clock->now()));
        $this->entityManager->flush();
    }
}
