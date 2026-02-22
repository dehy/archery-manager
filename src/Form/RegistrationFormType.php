<?php

declare(strict_types=1);

namespace App\Form;

use App\DBAL\Types\GenderType;
use App\Entity\User;
use App\Validator\Constraints\StrongPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
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
                'attr' => [
                    'class' => 'd-flex gap-3',
                ],
            ])
            ->add('firstname', null, [
                'label' => 'Prénom *',
                'required' => true,
            ])
            ->add('lastname', null, [
                'label' => 'Nom *',
                'required' => true,
            ])
            ->add('birthdate', BirthdayType::class, [
                'label' => 'Date de naissance *',
                'required' => true,
                'widget' => 'single_text',
                'help' => 'Vous devez avoir au moins 15 ans pour créer un compte. Si vous souhaitez inscrire un mineur de moins de 15 ans, vous devez d\'abord créer votre propre compte.',
                'attr' => [
                    'max' => new \DateTime('-15 years')->format('Y-m-d'),
                ],
            ])
            ->add('email', null, [
                'label' => 'Email *',
                'required' => true,
            ])
            ->add('phoneNumber', TelType::class, [
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
                    new IsTrue(message: "Vous devez accepter les conditions d'utilisation."),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe *',
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un mot de passe'),
                    new StrongPassword(minScore: 2),
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
