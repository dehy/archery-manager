<?php

declare(strict_types=1);

namespace App\Twig;

use Doctrine\Common\Util\ClassUtils;
use League\Flysystem\FilesystemOperator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;

class PublicUrlExtension extends AbstractExtension
{
    public function __construct(private readonly FilesystemOperator $clubsLogosStorage)
    {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('public_url', $this->publicUrl(...)),
        ];
    }

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
            throw new \Exception(\sprintf('No uploaded file found in object of class %s', $object::class));
        }

        if (\count($reflectionAttributes) > 1) {
            throw new \Exception(\sprintf('Multiple UploadableField found for object of class %s. Restrict with $propertyName property.', $object::class));
        }

        $reflectionAttribute = $reflectionAttributes[0];

        /** @var UploadableField $uploadableField */
        $uploadableField = $reflectionAttribute->newInstance();
        $mapping = $uploadableField->getMapping();
        $parts = array_map(fn (string $part): string => ucfirst($part), explode('.', $mapping));
        $storageName = lcfirst(implode('', $parts).'Storage');

        $filenameProperty = $uploadableField->getFileNameProperty();

        /** @var FilesystemOperator $operator */
        $operator = $this->{$storageName};

        $getter = \sprintf('get%s', ucfirst((string) $filenameProperty));

        return $operator->publicUrl($object->$getter());
    }
}
