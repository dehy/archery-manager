<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Club;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ClubRegistrationSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('acceptingApplications', CheckboxType::class, [
                'label' => 'Accepter les nouvelles inscriptions',
                'required' => false,
            ])
            ->add('applicationClosureMessage', TextareaType::class, [
                'label' => 'Raison affichée lorsque les inscriptions sont fermées',
                'required' => false,
                'help' => 'Exemple : Le club est complet pour cette saison.',
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Club::class,
        ]);
    }
}
