<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Applicant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicantRenewalType extends AbstractType
{
    #[\Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('lastname', null, [
                'label' => 'Nom *',
                'help' => 'Nom de famille du pratiquant',
                'required' => true,
            ])
            ->add('firstname', null, [
                'label' => 'Prénom *',
                'help' => 'Prénom du pratiquant',
                'required' => true,
            ])
            ->add('licenseNumber', null, [
                'label' => 'N° licence FFTA *',
                'required' => true,
            ])
            ->add('tournament', null, [
                'label' => 'Participation aux compétitions',
            ])
            ->add('comment', null, [
                'label' => 'Observations / Remarques',
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Applicant::class,
        ]);
    }
}
