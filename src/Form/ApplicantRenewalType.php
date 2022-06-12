<?php

namespace App\Form;

use App\DBAL\Types\LicenseType;
use App\Entity\Applicant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicantRenewalType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add("lastname", null, [
                "label" => "Nom *",
                "help" => "Nom de famille du pratiquant",
                "required" => true,
            ])
            ->add("firstname", null, [
                "label" => "Prénom *",
                "help" => "Prénom du pratiquant",
                "required" => true,
            ])
            ->add("licenseNumber", null, [
                "label" => "N° licence FFTA *",
                "required" => true,
            ])
            ->add("licenseType", ChoiceType::class, [
                "label" => "Type de Licence *",
                "choices" => [
                    "LOISIR" => "LOISIR",
                    "COMPÉTITION" => "COMPÉTITION",
                ],
                "expanded" => true,
                "required" => true,
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
