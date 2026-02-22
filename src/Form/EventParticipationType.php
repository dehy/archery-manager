<?php

declare(strict_types=1);

namespace App\Form;

use App\DBAL\Types\EventParticipationStateType;
use App\DBAL\Types\LicenseActivityType;
use App\Entity\EventParticipation;
use App\Entity\License;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventParticipationType extends AbstractType
{
    #[\Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        /** @var License|null $license */
        $license = $options['license_context'];
        $isContest = $options['is_contest'];

        $activityChoices = [];
        if ($license) {
            foreach ($license->getActivities() as $activity) {
                $activityChoices[LicenseActivityType::getReadableValue($activity)] = $activity;
            }
        } else {
            $activityChoices = LicenseActivityType::getChoices();
        }

        // Define participation state choices based on event type
        if ($isContest) {
            // For contests: 3 options
            $participationChoices = [
                "N'y va pas" => EventParticipationStateType::NOT_GOING,
                "Intéressé (je vais m'inscrire)" => EventParticipationStateType::INTERESTED,
                'Inscrit' => EventParticipationStateType::REGISTERED,
            ];
        } else {
            // For trainings: 2 options
            $participationChoices = [
                'Absent' => EventParticipationStateType::NOT_GOING,
                'Présent' => EventParticipationStateType::REGISTERED,
            ];
        }

        $builder
            ->add('participationState', ChoiceType::class, [
                'expanded' => true,
                'choices' => $participationChoices,
            ])
            ->add('activity', null, [
                'choices' => $activityChoices,
                'required' => true,
            ]);

        // Only add contest-specific fields for contest events
        if ($isContest) {
            $builder
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
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventParticipation::class,
            'license_context' => null,
            'is_contest' => false,
        ]);

        $resolver->setAllowedTypes('is_contest', 'bool');
    }
}
