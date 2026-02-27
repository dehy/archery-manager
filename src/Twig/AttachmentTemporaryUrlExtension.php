<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Attachment;
use App\Entity\User;
use App\Exception\StorageException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

class AttachmentTemporaryUrlExtension
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Security $security,
        private readonly FilesystemOperator $licenseesStorage,
        private readonly FilesystemOperator $eventsStorage,
    ) {
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'temporary_url')]
    public function temporaryUrl(Attachment $attachment): string
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $reflectionClass = new \ReflectionClass($attachment);
        $cacheKey = \sprintf(
            'user#%s.class#%s.name#%s.url',
            $user->getId(),
            $reflectionClass->getShortName(),
            $attachment->getFile()->getName()
        );

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($reflectionClass, $attachment) {
            $item->expiresAfter(new \DateInterval('PT10M'));

            $reflectionProperty = $reflectionClass->getProperty('uploadedFile');
            $reflectionAttributes = $reflectionProperty->getAttributes();
            foreach ($reflectionAttributes as $reflectionAttribute) {
                if (UploadableField::class === $reflectionAttribute->getName()) {
                    /** @var UploadableField $uploadableField */
                    $uploadableField = $reflectionAttribute->newInstance();
                    $storageName = $uploadableField->getMapping().'Storage';

                    /** @var FilesystemOperator|null $operator */
                    $operator = match ($storageName) {
                        'licenseesStorage' => $this->licenseesStorage,
                        'eventsStorage' => $this->eventsStorage,
                        default => null,
                    };
                    if (null === $operator) {
                        throw new \LogicException(\sprintf('Storage "%s" is not supported', $storageName));
                    }

                    return $operator->temporaryUrl(
                        $attachment->getFile()->getName(),
                        new \DateTime('+10 minutes')
                    );
                }
            }

            throw new StorageException('Was not able to generate a temporary URL');
        });
    }
}
