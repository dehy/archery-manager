<?php

namespace App\Entity;

use App\Repository\EventAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: EventAttachmentRepository::class)]
class PracticeAdviceAttachment extends Attachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticeAdvice $practiceAdvice = null;

    #[ORM\Column(type: 'PracticeAdviceAttachmentType')]
    private ?string $type = null;

    #[Vich\UploadableField(
        mapping: 'licensees',
        fileNameProperty: 'file.name',
        size: 'file.size',
        mimeType: 'file.mimeType',
        originalName: 'file.originalName',
        dimensions: 'file.dimensions'
    )]
    private UploadedFile|File|null $uploadedFile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPracticeAdvice(): ?PracticeAdvice
    {
        return $this->practiceAdvice;
    }

    public function setPracticeAdvice(?PracticeAdvice $practiceAdvice): self
    {
        $this->practiceAdvice = $practiceAdvice;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setUploadedFile(?File $file = null): void
    {
        $this->uploadedFile = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getUploadedFile(): ?File
    {
        return $this->uploadedFile;
    }
}
