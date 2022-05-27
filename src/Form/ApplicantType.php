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
        array $options
    ): void {
        $builder
            ->add("lastname", null, [
                "label" => "Nom *",
            ])
            ->add("firstname", null, [
                "label" => "Prénom *",
            ])
            ->add("birthdate", DateType::class, [
                "label" => "Date de naissance *",
                "widget" => "single_text",
                "help" => "L'âge minimum est de 8 ans à la date d'inscription.",
                "input" => "datetime_immutable",
            ])
            ->add("practiceLevel", null, [
                "label" => "Quel est votre niveau de pratique ?",
                "expanded" => true,
            ])
            ->add("licenseNumber", null, [
                "label" => "Si vous en avez déjà un, n° licence FFTA ?",
            ])
            ->add("email", null, [
                "label" => "Email *",
            ])
            ->add("phoneNumber", null, [
                "label" => "N° de téléphone *",
            ])
            ->add("comment", null, [
                "label" => "Observations / Remarques",
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Applicant::class,
        ]);
    }
}
