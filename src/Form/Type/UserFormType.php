<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\UserRoleType;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFormType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => GenderType::getChoices(),
                'required' => true,
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
        ;

        if ($options['is_admin']) {
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => UserRoleType::getChoices(),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_admin' => false,
        ]);
    }
}
