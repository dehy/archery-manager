<?php

namespace App\Form;

use App\DBAL\Types\LicenseActivityType;
use App\Entity\EventParticipation;
use App\Entity\License;
use App\Entity\User;
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
        /** @var License|null $license */
        $license = $options['license_context'];
        $activityChoices = [];
        if ($license) {
            foreach ($license->getActivities() as $activity) {
                $activityChoices[LicenseActivityType::getReadableValue($activity)] = $activity;
            }
        } else {
            $activityChoices = LicenseActivityType::getChoices();
        }

        $builder
            ->add('participationState', null, [
                'expanded' => true,
            ])
            ->add('activity', null, [
                'choices' => $activityChoices,
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
            'license_context' => null,
        ]);
    }
}
