<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Result;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventResultType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('licensee', null, [
                'disabled' => true,
            ])
            ->add('score1', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                    'max' => 300,
                ],
            ])
            ->add('score2', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                    'max' => 300,
                ],
            ])
            ->add('total', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                    'max' => 600,
                ],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Result::class,
        ]);
    }
}
