<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Licensee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LicenseeFormType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                ...$this->requiredValue(static fn (Licensee $licensee, string $value): Licensee => $licensee->setFirstname($value)),
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                ...$this->requiredValue(static fn (Licensee $licensee, string $value): Licensee => $licensee->setLastname($value)),
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Homme' => 'M',
                    'Femme' => 'F',
                    'Autre' => 'O',
                ],
                'required' => true,
                ...$this->requiredValue(static fn (Licensee $licensee, string $value): Licensee => $licensee->setGender($value)),
            ])
            ->add('birthdate', BirthdayType::class, [
                'label' => 'Date de naissance',
                'required' => true,
                'widget' => 'single_text',
                ...$this->requiredValue(static fn (Licensee $licensee, \DateTimeInterface $value): Licensee => $licensee->setBirthdate($value)),
            ])
            ->add('fftaMemberCode', TextType::class, [
                'label' => 'Code licence FFTA',
                'required' => false,
                'attr' => [
                    'placeholder' => '7 ou 8 caractères',
                    'maxlength' => 8,
                ],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Licensee::class,
        ]);
    }

    /**
     * Options of a field the licensee can't be without. A blank value is a validation error on the field: it is not
     * written to the licensee, whose setters don't take null (writing it would end in a TypeError, a 500).
     *
     * @param \Closure(Licensee, mixed): Licensee $set
     *
     * @return array{constraints: list<NotBlank>, setter: \Closure(Licensee, mixed): void}
     */
    private function requiredValue(\Closure $set): array
    {
        return [
            'constraints' => [new NotBlank(message: 'Ce champ est obligatoire.')],
            'setter' => static function (Licensee $licensee, mixed $value) use ($set): void {
                if (null !== $value) {
                    $set($licensee, $value);
                }
            },
        ];
    }
}
