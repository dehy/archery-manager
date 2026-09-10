<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FftaMemberCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('fftaMemberCode', TextType::class, [
            'label' => 'Code licence FFTA',
            'required' => true,
            'mapped' => false,
            'attr' => [
                'placeholder' => '7 ou 8 caractères',
                'maxlength' => 8,
                'pattern' => '[A-Za-z0-9]{7,8}',
            ],
        ]);
    }
}
