<?php

namespace App\Form;

use App\Entity\Applicant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicantType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('lastname', null, [
                'label' => 'Nom *',
                'required' => true,
            ])
            ->add('firstname', null, [
                'label' => 'Prénom *',
                'required' => true,
            ])
            ->add('birthdate', DateType::class, [
                'label' => 'Date de naissance *',
                'widget' => 'single_text',
                'help' => "L'âge minimum est de 8 ans à la date d'inscription.",
                'input' => 'datetime_immutable',
                'required' => true,
            ])
            ->add('practiceLevel', null, [
                'label' => 'Quel est votre niveau de pratique ?',
                'expanded' => true,
                'required' => true,
            ])
            ->add('licenseNumber', null, [
                'label' => 'Si vous en avez déjà un, n° licence FFTA ?',
            ])
            ->add('email', null, [
                'label' => 'Email *',
                'required' => true,
            ])
            ->add('phoneNumber', null, [
                'label' => 'N° de téléphone *',
                'required' => true,
            ])
            ->add('comment', null, [
                'label' => 'Observations / Remarques',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Applicant::class,
        ]);
    }
}
