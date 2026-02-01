<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LicenseApplicationRejectType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rejectionReason', TextareaType::class, [
                'label' => 'Motif du refus *',
                'required' => true,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Veuillez indiquer la raison du refus...',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Veuillez indiquer un motif de refus'),
                    new Assert\Length(
                        min: 10,
                        minMessage: 'Le motif doit contenir au moins {{ limit }} caractères',
                    ),
                ],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
