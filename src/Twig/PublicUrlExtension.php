<?php

declare(strict_types=1);

namespace App\Twig;

use App\Exception\StorageException;
use Doctrine\Common\Util\ClassUtils;
use League\Flysystem\FilesystemOperator;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

class PublicUrlExtension
{
    public function __construct(private readonly FilesystemOperator $clubsLogosStorage)
    {
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'public_url')]
    public function publicUrl(mixed $object, ?string $propertyName): string
    {
        $reflectionAttributes = [];

        $reflectionClass = new \ReflectionClass(ClassUtils::getRealClass($object::class));
        if (null !== $propertyName) {
            $reflectionProperty = $reflectionClass->getProperty($propertyName);
            $reflectionAttributes = $reflectionProperty->getAttributes(UploadableField::class);
        } else {
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $reflectionAttributes += $reflectionProperty->getAttributes(UploadableField::class);
            }
        }

        if (empty($reflectionAttributes)) {
            throw new StorageException(\sprintf('No uploaded file found in object of class %s', $object::class));
        }

        if (\count($reflectionAttributes) > 1) {
            throw new StorageException(\sprintf('Multiple UploadableField found for object of class %s. Restrict with $propertyName property.', $object::class));
        }

        $reflectionAttribute = $reflectionAttributes[0];

        /** @var UploadableField $uploadableField */
        $uploadableField = $reflectionAttribute->newInstance();
        $mapping = $uploadableField->getMapping();
        $parts = array_map(ucfirst(...), explode('_', $mapping));
        $storageName = lcfirst(implode('', $parts).'Storage');

        if ('clubsLogosStorage' === $storageName) {
            $operator = $this->clubsLogosStorage;
        } else {
            throw new \LogicException(\sprintf('Storage "%s" is not supported', $storageName));
        }

        $filenameProperty = $uploadableField->getFileNameProperty();

        $getter = \sprintf('get%s', ucfirst((string) $filenameProperty));

        return $operator->publicUrl($object->$getter());
    }
}
