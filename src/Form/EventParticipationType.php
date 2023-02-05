<?php

namespace App\Form;

use App\Entity\EventParticipation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventParticipationType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('participationState', null, [
                'expanded' => true,
            ])
            ->add('targetType')
            ->add('departure', ChoiceType::class, [
                'choices' => [
                    'n°1' => 1,
                    'n°2' => 2,
                    'n°3' => 3,
                    'n°4' => 4,
                ],
                'required' => false,
                'placeholder' => 'Unspecified',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventParticipation::class,
        ]);
    }
}
