<?php

declare(strict_types=1);

namespace App\Form;

use App\DBAL\Types\DisciplineType;
use App\Entity\FreeTrainingEvent;
use App\Entity\Group;
use App\Helper\ClubHelper;
use App\Repository\GroupRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FreeTrainingEventType extends AbstractType
{
    public function __construct(
        private readonly ClubHelper $clubHelper,
        private readonly GroupRepository $groupRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'événement',
                'attr' => [
                    'placeholder' => 'Ex: Entraînement libre du samedi',
                    'class' => 'form-control',
                ],
            ])
            ->add('discipline', ChoiceType::class, [
                'label' => 'Discipline',
                'choices' => DisciplineType::getChoices(),
                'attr' => ['class' => 'form-control'],
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('endsAt', DateTimeType::class, [
                'label' => 'Date et heure de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('allDay', CheckboxType::class, [
                'label' => 'Événement sur toute la journée',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'placeholder' => 'Adresse complète du lieu',
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude (optionnel)',
                'required' => false,
                'scale' => 6,
                'attr' => [
                    'placeholder' => '46.123456',
                    'class' => 'form-control',
                    'step' => 'any',
                ],
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude (optionnel)',
                'required' => false,
                'scale' => 6,
                'attr' => [
                    'placeholder' => '2.123456',
                    'class' => 'form-control',
                    'step' => 'any',
                ],
            ])
            ->add('assignedGroups', EntityType::class, [
                'class' => Group::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => 'Groupes assignés',
                'query_builder' => function () {
                    return $this->groupRepository->createQueryBuilder('g')
                        ->where('g.club = :club')
                        ->setParameter('club', $this->clubHelper->activeClub())
                        ->orderBy('g.name', 'ASC');
                },
                'attr' => [
                    'class' => 'form-check-group',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FreeTrainingEvent::class,
        ]);
    }
}
