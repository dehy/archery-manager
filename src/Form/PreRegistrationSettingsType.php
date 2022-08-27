<?php

namespace App\Form;

use Dmishh\SettingsBundle\Manager\SettingsManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreRegistrationSettingsType extends AbstractType
{
    public function __construct(protected SettingsManager $settingsManager)
    {
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder->add("waitingListActivated", CheckboxType::class, [
            "required" => false,
            "label" => "Liste d'attente",
            "data" => (bool) $this->settingsManager->get(
                "pre_registration_waiting_list_activated"
            ),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
