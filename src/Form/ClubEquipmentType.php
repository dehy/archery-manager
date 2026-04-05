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
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClubEquipmentType extends AbstractType
{
    private const string PLACEHOLDER_SELECT_TYPE = 'Sélectionnez un type';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'équipement',
                'choices' => ClubEquipmentTypeEnum::getChoices(),
                'choice_label' => ClubEquipmentTypeEnum::getReadableValue(...),
                'placeholder' => self::PLACEHOLDER_SELECT_TYPE,
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
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité en stock',
                'required' => true,
                'help' => 'Nombre d\'unités disponibles (ex: 6 arcs, 10 lots de 8 flèches)',
                'attr' => [
                    'min' => 1,
                ],
            ])
            ->add('purchasePrice', NumberType::class, [
                'label' => 'Prix d\'achat TTC (€)',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'help' => 'Prix unitaire TTC en euros',
                'attr' => [
                    'min' => 0,
                    'step' => '0.01',
                    'placeholder' => '0.00',
                ],
            ])
            ->add('purchaseDate', DateType::class, [
                'label' => "Date d'achat",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
        ;

        // Always add all type-specific fields; JS (equipment-type-toggle controller) handles visibility
        $this->addBowFields($builder);
        $this->addArrowFields($builder);
    }

    private function addBowFields(FormBuilderInterface $form): void
    {
        $form
            ->add('bowType', ChoiceType::class, [
                'label' => "Type d'arc",
                'choices' => BowType::getChoices(),
                'choice_label' => BowType::getReadableValue(...),
                'placeholder' => self::PLACEHOLDER_SELECT_TYPE,
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

    private function addArrowFields(FormBuilderInterface $form): void
    {
        $form
            ->add('arrowType', ChoiceType::class, [
                'label' => 'Type de flèche',
                'choices' => ArrowType::getChoices(),
                'choice_label' => ArrowType::getReadableValue(...),
                'placeholder' => self::PLACEHOLDER_SELECT_TYPE,
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
                'label' => "Type d'empennage",
                'choices' => FletchingType::getChoices(),
                'choice_label' => FletchingType::getReadableValue(...),
                'placeholder' => self::PLACEHOLDER_SELECT_TYPE,
                'required' => false,
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClubEquipment::class,
        ]);
    }
}
