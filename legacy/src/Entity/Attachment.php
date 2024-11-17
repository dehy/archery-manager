<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

#[ORM\MappedSuperclass]
#[ApiResource]
abstract class Attachment
{
    #[ORM\Embedded(class: EmbeddedFile::class)]
    protected EmbeddedFile $file;

    #[ORM\Column]
    protected ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->file = new EmbeddedFile();
    }

    public function setFile(EmbeddedFile $file): void
    {
        $this->file = $file;
    }

    public function getFile(): ?EmbeddedFile
    {
        return $this->file;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
