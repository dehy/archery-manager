<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventAttachmentRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: EventAttachmentRepository::class)]
#[Vich\Uploadable]
class EventAttachment extends Attachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Event $event = null;

    #[ORM\Column(type: 'EventAttachmentType')]
    #[Assert\NotNull]
    private ?string $type = null;

    #[Vich\UploadableField(
        mapping: 'events',
        fileNameProperty: 'file.name',
        size: 'file.size',
        mimeType: 'file.mimeType',
        originalName: 'file.originalName',
        dimensions: 'file.dimensions'
    )]
    #[Assert\File(
        extensions: ['pdf' => 'application/pdf']
    )]
    private ?File $uploadedFile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        $event->addAttachment($this);

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

        if ($file instanceof File) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getUploadedFile(): ?File
    {
        return $this->uploadedFile;
    }

    /**
     * @throws \Exception
     */
    public function generateFilename(): string
    {
        $event = $this->getEvent();
        $type = $this->getType();
        $discipline = $event->getDiscipline();
        $name = Slugify::create()->slugify($event->getName());
        $randomStr = bin2hex(random_bytes(4));

        return \sprintf(
            '%s-%s-%s-%s-%s',
            $event->getStartsAt()->format('Y-m-d'),
            $type,
            $discipline,
            $name,
            $randomStr
        );
    }
}
