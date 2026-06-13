<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\LicenseeAttachment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class LiceseeCaciUploadType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('uploadedFile', VichFileType::class, [
                'label' => 'Fichier (PDF, image…)',
                'required' => false,
                'download_uri' => false,
            ])
            ->add('documentDate', DateType::class, [
                'label' => 'Date du certificat',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LicenseeAttachment::class,
        ]);
    }
}
