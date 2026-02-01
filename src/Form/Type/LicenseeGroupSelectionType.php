<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Group;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LicenseeGroupSelectionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('groups', EntityType::class, [
                'label' => 'Groupes',
                'class' => Group::class,
                'choices' => $options['groups'],
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'groups' => [],
        ]);

        $resolver->setAllowedTypes('groups', 'array');
    }
}
