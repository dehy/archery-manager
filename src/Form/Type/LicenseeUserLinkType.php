<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LicenseeUserLinkType extends AbstractType
{
    private const string LABEL_EXISTING_USER = 'Utilisateur existant';

    private const string USER_DISPLAY_FORMAT = '%s %s (%s)';

    private const string PLACEHOLDER_SELECT_USER = 'Sélectionner un utilisateur';

    private const string LABEL_NEW_ACCOUNT_EMAIL = 'Email du nouveau compte';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user_choice', ChoiceType::class, [
                'label' => 'Compte utilisateur',
                'choices' => [
                    'Lier à un utilisateur existant' => 'existing',
                    'Créer un nouveau compte' => 'new',
                ],
                'expanded' => true,
                'required' => true,
                'data' => 'new',
            ])
            ->add('existing_user', EntityType::class, [
                'label' => self::LABEL_EXISTING_USER,
                'class' => User::class,
                'choice_label' => static fn (User $user): string => \sprintf(self::USER_DISPLAY_FORMAT, $user->getFirstname(), $user->getLastname(), $user->getEmail()),
                'placeholder' => self::PLACEHOLDER_SELECT_USER,
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => self::LABEL_NEW_ACCOUNT_EMAIL,
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\Email(),
                ],
            ])
        ;

        // Dynamic validation based on choice
        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if ('existing' === ($data['user_choice'] ?? null)) {
                // Remove and re-add existing_user field with required constraint
                $form->add('existing_user', EntityType::class, [
                    'label' => self::LABEL_EXISTING_USER,
                    'class' => User::class,
                    'choice_label' => static fn (User $user): string => \sprintf(self::USER_DISPLAY_FORMAT, $user->getFirstname(), $user->getLastname(), $user->getEmail()),
                    'placeholder' => self::PLACEHOLDER_SELECT_USER,
                    'required' => true,
                    'constraints' => [
                        new Assert\NotBlank(message: 'Veuillez sélectionner un utilisateur.'),
                    ],
                ]);

                // Email not required
                $form->add('email', EmailType::class, [
                    'label' => self::LABEL_NEW_ACCOUNT_EMAIL,
                    'required' => false,
                    'mapped' => false,
                    'constraints' => [
                        new Assert\Email(),
                    ],
                ]);
            } else {
                // existing_user not required
                $form->add('existing_user', EntityType::class, [
                    'label' => self::LABEL_EXISTING_USER,
                    'class' => User::class,
                    'choice_label' => static fn (User $user): string => \sprintf(self::USER_DISPLAY_FORMAT, $user->getFirstname(), $user->getLastname(), $user->getEmail()),
                    'placeholder' => self::PLACEHOLDER_SELECT_USER,
                    'required' => false,
                ]);

                // Email required for new user
                $form->add('email', EmailType::class, [
                    'label' => self::LABEL_NEW_ACCOUNT_EMAIL,
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [
                        new Assert\NotBlank(message: 'Veuillez saisir une adresse email.'),
                        new Assert\Email(),
                    ],
                ]);
            }
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
