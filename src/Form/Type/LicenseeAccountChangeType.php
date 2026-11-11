<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LicenseeAccountChangeType extends AbstractType
{
    final public const string DESTINATION_NEW = 'new';

    final public const string DESTINATION_EXISTING = 'existing';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destination', ChoiceType::class, [
                'label' => 'Destination',
                'choices' => [
                    'Créer un compte dédié' => self::DESTINATION_NEW,
                    'Rattacher à un compte existant du club' => self::DESTINATION_EXISTING,
                ],
                'expanded' => true,
                'data' => self::DESTINATION_NEW,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email du compte',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
                'attr' => ['placeholder' => 'prenom.nom@exemple.fr'],
            ]);

        if ($options['offer_account_deletion']) {
            $builder->add('delete_emptied_account', CheckboxType::class, [
                'label' => 'Supprimer l\'ancien compte (il n\'aura plus aucun licencié)',
                'required' => false,
                'data' => true,
            ]);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['offer_account_deletion' => false]);
        $resolver->setAllowedTypes('offer_account_deletion', 'bool');
    }
}
