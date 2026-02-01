<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreRegistrationSettingsType extends AbstractType
{
    #[\Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('waitingListActivated', CheckboxType::class, [
            'required' => false,
            'label' => "Liste d'attente",
            // TODO: Re-implement waiting list feature without dmishh/settings-bundle
            'data' => false,
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
