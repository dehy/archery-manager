<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class FftaLicenseeCsvUploadType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('csv', FileType::class, [
            'label' => 'Export CSV des licenciés FFTA',
            'mapped' => false,
            'constraints' => [
                new NotBlank(message: 'Veuillez sélectionner un fichier CSV.'),
                new File(
                    maxSize: '2M',
                    // Associative form so our own mime-type list is used as-is instead of
                    // being intersected with Symfony's default "csv" mime types (which
                    // notably excludes "text/plain", reported by some OSes, e.g. macOS).
                    extensions: [
                        'csv' => [
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'text/x-comma-separated-values',
                            'text/x-csv',
                        ],
                    ],
                    extensionsMessage: 'Veuillez sélectionner un fichier CSV valide.',
                ),
            ],
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
