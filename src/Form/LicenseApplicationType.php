<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Club;
use App\Entity\LicenseApplication;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseApplicationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('club', EntityType::class, [
                'label' => 'Club *',
                'class' => Club::class,
                'choice_label' => fn (Club $club): string => sprintf('%s - %s', $club->getCity(), $club->getName()),
                'placeholder' => 'Sélectionnez un club',
                'required' => true,
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->orderBy('c.city', 'ASC')
                    ->addOrderBy('c.name', 'ASC'),
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LicenseApplication::class,
        ]);
    }
}
