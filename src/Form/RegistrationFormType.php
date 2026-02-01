<?php

declare(strict_types=1);

namespace App\Form;

use App\DBAL\Types\GenderType;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    #[\Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre *',
                'choices' => [
                    'Homme' => GenderType::MALE,
                    'Femme' => GenderType::FEMALE,
                ],
                'required' => true,
                'expanded' => true,  // Radio buttons
                'label_attr' => ['class' => 'btn-check'],
                'choice_attr' => function() {
                    return ['class' => 'btn-check'];
                },
                'attr' => ['class' => 'btn-group'],
            ])
            ->add('firstname', null, [
                'label' => 'Prénom *',
                'required' => true,
            ])
            ->add('lastname', null, [
                'label' => 'Nom *',
                'required' => true,
            ])
            ->add('email', null, [
                'label' => 'Email *',
                'required' => true,
            ])
            ->add('phoneNumber', null, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => '06 12 34 56 78',
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label_html' => true,
                'constraints' => [
                    new IsTrue([
                        'message' => "Vous devez accepter les conditions d'utilisation.",
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe *',
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => "Votre mot de passe doit être d'au moins {{ limit }} caractères",
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
