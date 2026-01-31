<?php

declare(strict_types=1);

namespace App\Form;

use App\DBAL\Types\ArrowType;
use App\DBAL\Types\BowType;
use App\DBAL\Types\ClubEquipmentType as ClubEquipmentTypeEnum;
use App\DBAL\Types\FletchingType;
use App\Entity\ClubEquipment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClubEquipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'équipement',
                'choices' => ClubEquipmentTypeEnum::getChoices(),
                'choice_label' => fn ($choice) => ClubEquipmentTypeEnum::getReadableValue($choice),
                'placeholder' => 'Sélectionnez un type',
                'attr' => [
                    'data-equipment-type-target' => 'equipmentType',
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom / Identification',
                'help' => 'Ex: Arc Hoyt Recurve #1, Flèches Easton X10, etc.',
            ])
            ->add('serialNumber', TextType::class, [
                'label' => 'Numéro de série',
                'required' => false,
                'help' => 'Pour les arcs principalement',
            ])
            ->add('count', IntegerType::class, [
                'label' => 'Quantité',
                'required' => false,
                'help' => 'Pour les flèches, protections, etc.',
                'attr' => [
                    'min' => 1,
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
        ;

        // Add bow-specific fields conditionally
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $equipment = $event->getData();
            $form = $event->getForm();

            // Add bow-specific fields if equipment is a bow
            if ($equipment instanceof ClubEquipment && $equipment->getType() === ClubEquipmentTypeEnum::BOW) {
                $this->addBowFields($form);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            // Add bow-specific fields if type is bow
            if (isset($data['type']) && $data['type'] === ClubEquipmentTypeEnum::BOW) {
                $this->addBowFields($form);
            }

            // Add arrow-specific fields if type is arrows
            if (isset($data['type']) && $data['type'] === ClubEquipmentTypeEnum::ARROWS) {
                $this->addArrowFields($form);
            }
        });
    }

    private function addBowFields($form): void
    {
        $form
            ->add('bowType', ChoiceType::class, [
                'label' => 'Type d\'arc',
                'choices' => BowType::getChoices(),
                'choice_label' => fn ($choice) => BowType::getReadableValue($choice),
                'placeholder' => 'Sélectionnez un type',
                'required' => false,
            ])
            ->add('brand', TextType::class, [
                'label' => 'Marque',
                'required' => false,
            ])
            ->add('model', TextType::class, [
                'label' => 'Modèle',
                'required' => false,
            ])
            ->add('limbSize', TextType::class, [
                'label' => 'Taille des branches',
                'required' => false,
                'help' => 'Ex: 68", 70"',
            ])
            ->add('limbStrength', TextType::class, [
                'label' => 'Puissance des branches',
                'required' => false,
                'help' => 'Ex: 28#, 32#',
            ])
        ;
    }

    private function addArrowFields($form): void
    {
        $form
            ->add('arrowType', ChoiceType::class, [
                'label' => 'Type de flèche',
                'choices' => ArrowType::getChoices(),
                'choice_label' => fn ($choice) => ArrowType::getReadableValue($choice),
                'placeholder' => 'Sélectionnez un type',
                'required' => false,
            ])
            ->add('arrowLength', TextType::class, [
                'label' => 'Longueur',
                'required' => false,
                'help' => 'Ex: 28", 30"',
            ])
            ->add('arrowSpine', TextType::class, [
                'label' => 'Spine',
                'required' => false,
                'help' => 'Ex: 500, 600',
            ])
            ->add('fletchingType', ChoiceType::class, [
                'label' => 'Type d\'empennage',
                'choices' => FletchingType::getChoices(),
                'choice_label' => fn ($choice) => FletchingType::getReadableValue($choice),
                'placeholder' => 'Sélectionnez un type',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClubEquipment::class,
        ]);
    }
}
