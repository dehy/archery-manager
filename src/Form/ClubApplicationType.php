<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Club;
use App\Entity\ClubApplication;
use App\Entity\Licensee;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClubApplicationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['show_licensee_selector']) {
            $userLicensees = $options['user_licensees'];
            $builder->add('licensee', EntityType::class, [
                'label' => 'Licencié *',
                'class' => Licensee::class,
                'choices' => $userLicensees,
                'choice_label' => static fn (Licensee $l): string => $l->getFirstname().' '.$l->getLastname(),
                'placeholder' => 'Sélectionnez un licencié',
                'required' => true,
            ]);
        }

        $builder
            ->add('club', EntityType::class, [
                'label' => 'Club *',
                'class' => Club::class,
                'choice_label' => static fn (Club $club): string => \sprintf('%s - %s', $club->getCity(), $club->getName()),
                'placeholder' => 'Sélectionnez un club',
                'required' => true,
                'query_builder' => static fn (EntityRepository $er): \Doctrine\ORM\QueryBuilder => $er->createQueryBuilder('c')
                    ->orderBy('c.city', 'ASC')
                    ->addOrderBy('c.name', 'ASC'),
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClubApplication::class,
            'show_licensee_selector' => false,
            'user_licensees' => [],
        ]);

        $resolver->setAllowedTypes('show_licensee_selector', 'bool');
        $resolver->setAllowedTypes('user_licensees', 'iterable');
    }
}
