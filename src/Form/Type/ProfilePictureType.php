<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Service\ProfilePictureManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Uploads a licensee's profile picture (see ProfilePictureManager).
 */
class ProfilePictureType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('picture', FileType::class, [
            'label' => 'Nouvelle photo',
            'help' => 'JPEG, PNG ou WebP, 5 Mo maximum, au moins 100 × 100 pixels.',
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new Assert\NotNull(message: 'Choisissez une photo.'),
                new Assert\Image(
                    maxSize: '5M',
                    mimeTypes: ProfilePictureManager::MIME_TYPES,
                    minWidth: 100,
                    minHeight: 100,
                    mimeTypesMessage: 'La photo doit être une image JPEG, PNG ou WebP.',
                ),
            ],
            'attr' => [
                'accept' => implode(',', ProfilePictureManager::MIME_TYPES),
                'data-action' => 'change->picture-preview#preview',
                'data-picture-preview-target' => 'input',
            ],
        ]);
    }
}
