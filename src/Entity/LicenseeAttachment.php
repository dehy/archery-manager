<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LicenseeAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: LicenseeAttachmentRepository::class)]
#[Vich\Uploadable]
class LicenseeAttachment extends Attachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Licensee $licensee = null;

    #[ORM\Column(nullable: true)]
    private ?int $season = null;

    #[ORM\Column(type: 'LicenseeAttachmentType')]
    private ?string $type = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $documentDate = null;

    #[Vich\UploadableField(
        mapping: 'licensees',
        fileNameProperty: 'file.name',
        size: 'file.size',
        mimeType: 'file.mimeType',
        originalName: 'file.originalName',
        dimensions: 'file.dimensions'
    )]
    private ?File $uploadedFile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLicensee(): ?Licensee
    {
        return $this->licensee;
    }

    public function setLicensee(?Licensee $licensee): self
    {
        $this->licensee = $licensee;

        return $this;
    }

    public function getSeason(): ?int
    {
        return $this->season;
    }

    public function setSeason(int $season): self
    {
        $this->season = $season;

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

    public function getDocumentDate(): ?\DateTimeImmutable
    {
        return $this->documentDate;
    }

    public function setDocumentDate(\DateTimeImmutable $documentDate): self
    {
        $this->documentDate = $documentDate;

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
        $licensee = $this->getLicensee();
        $type = $this->getType();
        $randomStr = bin2hex(random_bytes(4));

        if (null !== $this->getSeason() && 0 !== $this->getSeason()) {
            return \sprintf('%s-%s-%s-%s', $licensee->getFftaMemberCode(), $this->getSeason(), $type, $randomStr);
        }

        return \sprintf('%s-%s-%s', $licensee->getFftaMemberCode(), $type, $randomStr);
    }

    public function __serialize()
    {
        return ['id' => $this->getId()];
    }

    public function __unserialize(array $data)
    {
        $this->id = $data['id'];
    }
}
