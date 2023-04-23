<?php

namespace App\Twig;

use Doctrine\Common\Util\ClassUtils;
use League\Flysystem\FilesystemOperator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;

class PublicUrlExtension extends AbstractExtension
{
    public function __construct(private readonly FilesystemOperator $clubsLogosStorage) {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('public_url', $this->publicUrl(...)),
        ];
    }

    public function publicUrl(mixed $object, string $propertyName): string
    {
        $reflectionProperty = new \ReflectionProperty(ClassUtils::getRealClass(get_class($object)), $propertyName);
        $reflectionAttributes = $reflectionProperty->getAttributes();
        foreach ($reflectionAttributes as $reflectionAttribute) {
            if (UploadableField::class === $reflectionAttribute->getName()) {
                /** @var UploadableField $uploadableField */
                $uploadableField = $reflectionAttribute->newInstance();
                $mapping = $uploadableField->getMapping();
                $parts = array_map(fn (string $part) => ucfirst($part), explode('.', $mapping));
                $storageName = lcfirst(implode("", $parts).'Storage');

                /** @var FilesystemOperator $operator */
                $operator = $this->{$storageName};

                $getter = sprintf('get%s', ucfirst($propertyName));
                return $operator->publicUrl($object->$getter());
            }
        }

        throw new \Exception('Was not able to get the public URL');
    }
}
